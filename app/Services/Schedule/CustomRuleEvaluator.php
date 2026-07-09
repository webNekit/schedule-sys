<?php

declare(strict_types=1);

namespace App\Services\Schedule;

use App\Models\CustomSchedulingRule;
use App\Models\ScheduleLesson;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Универсальный исполнитель авторских правил расписания.
 *
 * Читает {@see CustomSchedulingRule} и проверяет уже сгенерированные пары,
 * добавляя нарушения в общий список конфликтов (в том же формате, что и
 * встроенные проверки {@see ConflictCheckerService}).
 *
 * Поддерживаются три типа правил (definition.type):
 *  - forbid:  любая пара, попавшая под `match`, — нарушение (запрет по дню/паре и т.п.);
 *  - require: пара из `match`, не удовлетворяющая `require`, — нарушение (привязки);
 *  - limit:   агрегат `count` по ключу `group_by` нарушает `op value` (лимиты макс/мин).
 *
 * Условие (`match`/`require`) рекурсивно: {all:[…]} | {any:[…]} | {not:{…}} | {field,op,value}.
 */
class CustomRuleEvaluator
{
    /** Кэш жёстких правил forbid/require для проверки размещения на этапе генерации. */
    private ?array $placementRules = null;

    /**
     * Загрузить (или перезагрузить) жёсткие правила, пригодные для проверки
     * размещения на лету: только forbid/require (агрегатные limit здесь неприменимы).
     */
    public function loadPlacementRules(): void
    {
        $this->placementRules = CustomSchedulingRule::enabled()
            ->where('severity', 'hard')
            ->get()
            ->filter(fn (CustomSchedulingRule $r) => in_array($r->definition['type'] ?? '', ['forbid', 'require'], true))
            ->values()
            ->all();
    }

    /**
     * Можно ли поставить пару с такими параметрами, не нарушив жёсткое правило.
     *
     * @param  array<string, mixed>  $record  Кандидат: group_id, teacher_id, room_id,
     *                                        building_id, discipline_id, lesson_type,
     *                                        lesson_number, weekday, date, course, shift.
     */
    public function allowsPlacement(array $record): bool
    {
        if ($this->placementRules === null) {
            $this->loadPlacementRules();
        }

        foreach ($this->placementRules as $rule) {
            if (! $this->inScope($rule, $record)) {
                continue;
            }

            $def = $rule->definition ?? [];
            $match = $def['match'] ?? null;
            if ($match !== null && ! $this->matches($match, $record)) {
                continue;
            }

            if (($def['type'] ?? null) === 'forbid') {
                return false;
            }

            if (($def['type'] ?? null) === 'require') {
                $require = $def['require'] ?? null;
                if ($require === null || ! $this->matches($require, $record)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Проверить пары версии по всем включённым авторским правилам.
     *
     * @param  Collection<int, ScheduleLesson>  $lessons
     * @param  array<int, array<string, mixed>>  $conflicts  Накопитель конфликтов (по ссылке).
     */
    public function evaluate(Collection $lessons, int $versionId, array &$conflicts): void
    {
        $rules = CustomSchedulingRule::enabled()->get();
        if ($rules->isEmpty()) {
            return;
        }

        $records = $lessons->map(fn (ScheduleLesson $l) => $this->toRecord($l))->all();

        foreach ($rules as $rule) {
            $scoped = array_values(array_filter($records, fn (array $r) => $this->inScope($rule, $r)));
            $this->evaluateRule($rule, $scoped, $versionId, $conflicts);
        }
    }

    /**
     * Плоское представление пары для проверки условий.
     *
     * @return array<string, mixed>
     */
    private function toRecord(ScheduleLesson $lesson): array
    {
        $date = $lesson->date instanceof Carbon ? $lesson->date : Carbon::parse($lesson->date);

        return [
            'lesson_id' => $lesson->id,
            'group_id' => $lesson->group_id,
            'teacher_id' => $lesson->teacher_id,
            'room_id' => $lesson->room_id,
            'building_id' => $lesson->room?->building_id,
            'room_type_id' => $lesson->room?->room_type_id,
            'discipline_id' => $lesson->discipline_id,
            'lesson_type' => $lesson->lessonType?->code,
            'lesson_number' => $lesson->lesson_number,
            'weekday' => $date->dayOfWeekIso,
            'date' => $date->toDateString(),
            'course' => $lesson->group?->current_course,
            'shift' => $lesson->group?->shift,
        ];
    }

    /**
     * Распространяется ли правило на данную пару (уровень global/course/group).
     *
     * @param  array<string, mixed>  $record
     */
    private function inScope(CustomSchedulingRule $rule, array $record): bool
    {
        return match ($rule->scope) {
            'course' => (int) $record['course'] === (int) $rule->scope_id,
            'group' => (int) $record['group_id'] === (int) $rule->scope_id,
            default => true,
        };
    }

    /**
     * @param  array<int, array<string, mixed>>  $scoped
     * @param  array<int, array<string, mixed>>  $conflicts
     */
    private function evaluateRule(CustomSchedulingRule $rule, array $scoped, int $versionId, array &$conflicts): void
    {
        $def = $rule->definition ?? [];
        $type = $def['type'] ?? null;
        $match = $def['match'] ?? null;
        $severity = $rule->severity === 'hard' ? 'error' : 'warning';

        $matched = array_values(array_filter(
            $scoped,
            fn (array $r) => $match === null || $this->matches($match, $r),
        ));

        switch ($type) {
            case 'forbid':
                foreach ($matched as $r) {
                    $conflicts[] = $this->conflictRow($rule, $versionId, $severity, $r, 'нарушает правило (запрещённое занятие)');
                }
                break;

            case 'require':
                $require = $def['require'] ?? null;
                foreach ($matched as $r) {
                    if ($require === null || ! $this->matches($require, $r)) {
                        $conflicts[] = $this->conflictRow($rule, $versionId, $severity, $r, 'не удовлетворяет требуемому условию');
                    }
                }
                break;

            case 'limit':
                $this->evaluateLimit($rule, $def, $matched, $versionId, $severity, $conflicts);
                break;
        }
    }

    /**
     * Агрегатное ограничение: считаем пары по ключу group_by и сверяем с порогом.
     *
     * @param  array<string, mixed>  $def
     * @param  array<int, array<string, mixed>>  $matched
     * @param  array<int, array<string, mixed>>  $conflicts
     */
    private function evaluateLimit(CustomSchedulingRule $rule, array $def, array $matched, int $versionId, string $severity, array &$conflicts): void
    {
        $groupBy = is_array($def['group_by'] ?? null) ? $def['group_by'] : [];
        $op = $def['op'] ?? '<=';
        $threshold = (int) ($def['value'] ?? 0);

        $buckets = [];
        foreach ($matched as $r) {
            $key = implode('|', array_map(fn ($f) => (string) ($r[$f] ?? ''), $groupBy));
            $buckets[$key][] = $r;
        }

        foreach ($buckets as $rows) {
            $count = count($rows);
            if ($this->compare($count, $op, $threshold)) {
                continue;
            }

            // К нарушению привязываем только те измерения, что задают группу (group_by).
            $representative = $rows[0];
            $attach = [];
            foreach (['date', 'group_id', 'teacher_id', 'room_id', 'discipline_id'] as $field) {
                $attach[$field] = in_array($field, $groupBy, true) ? $representative[$field] : null;
            }

            $conflicts[] = $this->conflictRow(
                $rule,
                $versionId,
                $severity,
                $representative,
                "превышен лимит: {$count} при пороге {$op} {$threshold}",
                lessonNumber: null,
                overrides: $attach,
            );
        }
    }

    /**
     * Проверка рекурсивного условия над записью пары.
     *
     * @param  array<string, mixed>  $condition
     * @param  array<string, mixed>  $record
     */
    private function matches(array $condition, array $record): bool
    {
        if (isset($condition['all']) && is_array($condition['all'])) {
            foreach ($condition['all'] as $sub) {
                if (! $this->matches($sub, $record)) {
                    return false;
                }
            }

            return true;
        }

        if (isset($condition['any']) && is_array($condition['any'])) {
            foreach ($condition['any'] as $sub) {
                if ($this->matches($sub, $record)) {
                    return true;
                }
            }

            return false;
        }

        if (isset($condition['not']) && is_array($condition['not'])) {
            return ! $this->matches($condition['not'], $record);
        }

        if (isset($condition['field'])) {
            $actual = $record[$condition['field']] ?? null;

            return $this->compare($actual, $condition['op'] ?? '=', $condition['value'] ?? null);
        }

        // Пустое условие трактуем как «подходит всё».
        return true;
    }

    /**
     * Сравнение значения с порогом по оператору.
     */
    private function compare(mixed $actual, string $op, mixed $value): bool
    {
        return match ($op) {
            '=' => $actual == $value,
            '!=' => $actual != $value,
            'in' => in_array($actual, (array) $value),
            'not_in' => ! in_array($actual, (array) $value),
            '<' => $actual !== null && $actual < $value,
            '<=' => $actual !== null && $actual <= $value,
            '>' => $actual !== null && $actual > $value,
            '>=' => $actual !== null && $actual >= $value,
            default => false,
        };
    }

    /**
     * Сформировать строку конфликта в формате таблицы schedule_conflicts.
     *
     * @param  array<string, mixed>  $record
     * @param  array<string, mixed>  $overrides  Принудительные значения привязок (для агрегатов).
     * @return array<string, mixed>
     */
    private function conflictRow(
        CustomSchedulingRule $rule,
        int $versionId,
        string $severity,
        array $record,
        string $detail,
        ?int $lessonNumber = -1,
        array $overrides = [],
    ): array {
        $pick = fn (string $field) => array_key_exists($field, $overrides) ? $overrides[$field] : ($record[$field] ?? null);

        return [
            'version_id' => $versionId,
            'conflict_type' => 'custom_rule',
            'severity' => $severity,
            'date' => $pick('date'),
            'lesson_number' => $lessonNumber === -1 ? ($record['lesson_number'] ?? null) : $lessonNumber,
            'group_id' => $pick('group_id'),
            'teacher_id' => $pick('teacher_id'),
            'room_id' => $pick('room_id'),
            'discipline_id' => $pick('discipline_id'),
            'description' => "[{$rule->name}] {$detail}",
            'suggestion' => $rule->description,
        ];
    }
}
