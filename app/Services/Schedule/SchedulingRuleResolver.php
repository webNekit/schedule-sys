<?php

declare(strict_types=1);

namespace App\Services\Schedule;

use App\Models\Group;
use App\Models\SchedulingRule;
use Illuminate\Support\Collection;

/**
 * Единая точка чтения правил генерации расписания.
 *
 * Правила разрешаются по приоритету: группа → курс → глобально → встроенный дефолт.
 * Параметры (params) сливаются: частичное переопределение на уровне группы дополняет
 * глобальные значения. is_enabled и severity берутся из самой специфичной заданной строки.
 *
 * Встроенные дефолты повторяют прежнее «вшитое» поведение, поэтому система работает
 * корректно даже с пустой таблицей scheduling_rules.
 */
class SchedulingRuleResolver
{
    /**
     * Встроенные значения по умолчанию.
     *
     * @var array<string, array{is_enabled: bool, severity: ?string, params: array<string, mixed>}>
     */
    public const DEFAULTS = [
        // ── Параметры генерации ──────────────────────────────────────────
        'pairs_per_day' => ['is_enabled' => true, 'severity' => null, 'params' => ['base' => 3, 'max' => 5]],
        'default_slots' => ['is_enabled' => true, 'severity' => null, 'params' => ['shift1' => [1, 2, 3, 4, 5], 'shift2' => [3, 4, 5, 6, 7]]],
        'discipline_ranking' => ['is_enabled' => true, 'severity' => null, 'params' => [
            'low_hours_threshold' => 10,
            'low_hours_boost' => 1000000,
            'repeat_week_penalty' => 5000,
            'max_per_week' => 2,
            'min_days_gap' => 2,
            'recent_gap_penalty' => 10000,
            'top_n_random' => 3,
        ]],
        'lesson_type_order' => ['is_enabled' => true, 'severity' => null, 'params' => ['order' => ['lecture', 'practice', 'lab']]],
        'pe_block' => ['is_enabled' => true, 'severity' => null, 'params' => ['doubled' => true, 'max_per_week' => 1, 'max_start_slot' => 3]],
        'building_rotation' => ['is_enabled' => true, 'severity' => null, 'params' => ['strategy' => 'by_weekday']],
        'min_lessons_check_weekdays' => ['is_enabled' => true, 'severity' => null, 'params' => ['days' => [1, 2, 3, 4, 5]]],

        // ── Ограничения (is_enabled + severity) ──────────────────────────
        'group_no_windows' => ['is_enabled' => true, 'severity' => 'hard', 'params' => []],
        'teacher_no_windows' => ['is_enabled' => true, 'severity' => 'soft', 'params' => []],
        'one_building_per_day_group' => ['is_enabled' => true, 'severity' => 'hard', 'params' => []],
        'one_building_per_day_teacher' => ['is_enabled' => true, 'severity' => 'hard', 'params' => []],
        'teacher_discipline_match' => ['is_enabled' => true, 'severity' => 'hard', 'params' => []],
        'room_capacity' => ['is_enabled' => true, 'severity' => 'soft', 'params' => []],
        'group_min_lessons' => ['is_enabled' => true, 'severity' => 'soft', 'params' => ['min' => 3]],
        'teacher_min_lessons' => ['is_enabled' => true, 'severity' => 'soft', 'params' => ['min' => 2]],
        'group_weekly_overload' => ['is_enabled' => true, 'severity' => 'soft', 'params' => ['max_hours' => 36]],
        'teacher_weekly_overload' => ['is_enabled' => true, 'severity' => 'soft', 'params' => ['max_hours' => 36]],
        'pe_grouping' => ['is_enabled' => true, 'severity' => 'soft', 'params' => []],
    ];

    /** Все строки правил, сгруппированные по key. Лениво загружается и кэшируется. */
    private ?Collection $rules = null;

    /**
     * Загрузить (или перезагрузить) правила из БД. Вызывается в начале сеанса генерации/проверки.
     */
    public function load(): void
    {
        $this->rules = SchedulingRule::all()->groupBy('key');
    }

    private function rules(): Collection
    {
        if ($this->rules === null) {
            $this->load();
        }

        return $this->rules;
    }

    public function isEnabled(string $key, ?Group $group = null): bool
    {
        return (bool) $this->resolveField($key, 'is_enabled', $group);
    }

    /**
     * Жёсткость ограничения: 'hard' | 'soft'. Для параметров вернёт дефолт (обычно null).
     */
    public function severity(string $key, ?Group $group = null): ?string
    {
        return $this->resolveField($key, 'severity', $group);
    }

    public function param(string $key, string $name, mixed $default = null, ?Group $group = null): mixed
    {
        $params = $this->resolveParams($key, $group);

        return $params[$name] ?? $default;
    }

    public function intParam(string $key, string $name, int $default = 0, ?Group $group = null): int
    {
        return (int) $this->param($key, $name, $default, $group);
    }

    /**
     * @return array<int, mixed>
     */
    public function arrayParam(string $key, string $name, array $default = [], ?Group $group = null): array
    {
        $value = $this->param($key, $name, $default, $group);

        return is_array($value) ? $value : $default;
    }

    public function boolParam(string $key, string $name, bool $default = false, ?Group $group = null): bool
    {
        return (bool) $this->param($key, $name, $default, $group);
    }

    /**
     * Слитые параметры: дефолт ← global ← course ← group.
     *
     * @return array<string, mixed>
     */
    public function resolveParams(string $key, ?Group $group = null): array
    {
        $params = self::DEFAULTS[$key]['params'] ?? [];

        foreach ($this->matchingRows($key, $group) as $row) {
            if (is_array($row->params)) {
                $params = array_merge($params, $row->params);
            }
        }

        return $params;
    }

    /**
     * Значение поля (is_enabled/severity) из самой специфичной заданной строки.
     */
    private function resolveField(string $key, string $field, ?Group $group): mixed
    {
        $value = self::DEFAULTS[$key][$field] ?? null;

        foreach ($this->matchingRows($key, $group) as $row) {
            $value = $row->{$field};
        }

        return $value;
    }

    /**
     * Подходящие строки в порядке возрастания приоритета: global → course → group.
     * Так последующий array_merge / присваивание перекрывает менее специфичные.
     *
     * @return array<int, SchedulingRule>
     */
    private function matchingRows(string $key, ?Group $group): array
    {
        $rows = $this->rules()->get($key);
        if (! $rows) {
            return [];
        }

        $global = $rows->firstWhere('scope', 'global');
        $course = $group ? $rows->first(fn ($r) => $r->scope === 'course' && (int) $r->scope_id === (int) $group->current_course) : null;
        $groupRow = $group ? $rows->first(fn ($r) => $r->scope === 'group' && (int) $r->scope_id === (int) $group->id) : null;

        return array_values(array_filter([$global, $course, $groupRow]));
    }
}
