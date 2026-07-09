<?php

declare(strict_types=1);

namespace App\Http\Livewire\Admin;

use App\Models\Building;
use App\Models\CurriculumDiscipline;
use App\Models\CustomSchedulingRule;
use App\Models\Group;
use App\Models\LessonType;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Teacher;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class CustomRules extends Component
{
    /** Доступные поля пары для условий. */
    public const FIELDS = [
        'group_id', 'teacher_id', 'room_id', 'building_id', 'room_type_id', 'discipline_id',
        'lesson_type', 'lesson_number', 'weekday', 'date', 'course', 'shift',
    ];

    /** Допустимые операторы сравнения. */
    public const OPS = ['=', '!=', '<', '<=', '>', '>=', 'in', 'not_in'];

    /** Человекочитаемые подписи полей. */
    public const FIELD_LABELS = [
        'group_id' => 'Группа',
        'teacher_id' => 'Преподаватель',
        'room_id' => 'Аудитория',
        'building_id' => 'Корпус',
        'room_type_id' => 'Тип аудитории',
        'discipline_id' => 'Дисциплина',
        'lesson_type' => 'Тип занятия',
        'lesson_number' => 'Номер пары',
        'weekday' => 'День недели',
        'date' => 'Дата',
        'course' => 'Курс',
        'shift' => 'Смена',
    ];

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $description = '';

    public string $scope = 'global';

    public ?int $scopeId = null;

    public string $severity = 'soft';

    public bool $isEnabled = true;

    // ── Визуальный конструктор ───────────────────────────────────────────────
    public string $type = 'forbid';

    public string $matchCombinator = 'all';

    /** @var array<int, array{field: string, op: string, value: mixed}> */
    public array $matchConditions = [];

    public string $requireCombinator = 'all';

    /** @var array<int, array{field: string, op: string, value: mixed}> */
    public array $requireConditions = [];

    /** @var array<int, string> */
    public array $limitGroupBy = ['teacher_id', 'date'];

    public string $limitOp = '<=';

    public int $limitValue = 4;

    // ── Расширенный режим (сырой JSON для вложенной логики) ───────────────────
    public bool $advanced = false;

    public string $definitionJson = '';

    public function newRule(): void
    {
        $this->resetForm();
        $this->setType('forbid');
        $this->showForm = true;
    }

    public function setType(string $type): void
    {
        $this->type = in_array($type, ['forbid', 'require', 'limit'], true) ? $type : 'forbid';

        if ($this->matchConditions === []) {
            $this->matchConditions = [$this->blankCondition('weekday')];
        }
        if ($this->type === 'require' && $this->requireConditions === []) {
            $this->requireConditions = [$this->blankCondition('building_id')];
        }
    }

    public function addMatchCondition(): void
    {
        $this->matchConditions[] = $this->blankCondition('group_id');
    }

    public function removeMatchCondition(int $i): void
    {
        unset($this->matchConditions[$i]);
        $this->matchConditions = array_values($this->matchConditions);
    }

    public function addRequireCondition(): void
    {
        $this->requireConditions[] = $this->blankCondition('building_id');
    }

    public function removeRequireCondition(int $i): void
    {
        unset($this->requireConditions[$i]);
        $this->requireConditions = array_values($this->requireConditions);
    }

    /**
     * При смене поля в строке условия сбрасываем оператор и значение под новый тип поля.
     */
    public function updated(string $name, mixed $value): void
    {
        if (preg_match('/^(matchConditions|requireConditions)\.(\d+)\.field$/', $name, $m)) {
            $prop = $m[1];
            $i = (int) $m[2];
            $this->{$prop}[$i]['op'] = $this->defaultOp((string) $value);
            $this->{$prop}[$i]['value'] = $this->isMultiValueField((string) $value) ? [] : '';
        }
    }

    public function edit(int $id): void
    {
        $rule = CustomSchedulingRule::findOrFail($id);
        $this->resetForm();
        $this->editingId = $rule->id;
        $this->name = $rule->name;
        $this->description = (string) $rule->description;
        $this->scope = $rule->scope;
        $this->scopeId = $rule->scope_id;
        $this->severity = $rule->severity;
        $this->isEnabled = $rule->is_enabled;

        $this->loadIntoBuilder($rule->definition ?? []);
        $this->showForm = true;
    }

    public function updatedScope(): void
    {
        $this->scopeId = $this->scope === 'course' ? 1 : ($this->scope === 'group'
            ? Group::where('is_active', true)->orderBy('name')->value('id')
            : null);
    }

    public function save(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'scope' => 'required|in:global,course,group',
            'severity' => 'required|in:hard,soft',
        ]);

        $definition = $this->advanced ? $this->parseJsonDefinition() : $this->buildDefinition();
        if ($definition === null) {
            return; // ошибка уже добавлена
        }

        $errors = $this->validateDefinition($definition);
        if ($errors !== []) {
            $this->addError('definition', implode(' ', $errors));

            return;
        }

        CustomSchedulingRule::updateOrCreate(
            ['id' => $this->editingId],
            [
                'name' => $this->name,
                'description' => $this->description ?: null,
                'scope' => $this->scope,
                'scope_id' => $this->scope === 'global' ? null : (int) $this->scopeId,
                'severity' => $this->severity,
                'is_enabled' => $this->isEnabled,
                'definition' => $definition,
            ],
        );

        $this->showForm = false;
        $this->resetForm();
        session()->flash('message', 'Правило сохранено');
    }

    public function delete(int $id): void
    {
        CustomSchedulingRule::whereKey($id)->delete();
        session()->flash('message', 'Правило удалено');
    }

    public function toggle(int $id): void
    {
        $rule = CustomSchedulingRule::findOrFail($id);
        $rule->update(['is_enabled' => ! $rule->is_enabled]);
    }

    public function cancel(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    /**
     * Переключение в расширенный режим показывает собранный JSON, обратно — пытается разобрать его.
     */
    public function updatedAdvanced(bool $value): void
    {
        if ($value) {
            $built = $this->buildDefinition() ?? ['type' => $this->type];
            $this->definitionJson = json_encode($built, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } else {
            $decoded = json_decode($this->definitionJson, true);
            if (is_array($decoded)) {
                $this->loadIntoBuilder($decoded);
            }
        }
    }

    // ── Построение / разбор definition ───────────────────────────────────────

    /**
     * Собрать definition из состояния визуального конструктора.
     *
     * @return array<string, mixed>|null
     */
    private function buildDefinition(): ?array
    {
        $match = $this->buildCondition($this->matchCombinator, $this->matchConditions);

        if ($this->type === 'forbid') {
            if ($match === null) {
                $this->addError('definition', 'Добавьте хотя бы одно условие.');

                return null;
            }

            return ['type' => 'forbid', 'match' => $match];
        }

        if ($this->type === 'require') {
            $require = $this->buildCondition($this->requireCombinator, $this->requireConditions);
            if ($match === null || $require === null) {
                $this->addError('definition', 'Для привязки нужны условия «для каких пар» и «обязательно».');

                return null;
            }

            return ['type' => 'require', 'match' => $match, 'require' => $require];
        }

        // limit
        if ($this->limitGroupBy === []) {
            $this->addError('definition', 'Для лимита выберите хотя бы одно поле группировки.');

            return null;
        }
        $def = ['type' => 'limit', 'group_by' => array_values($this->limitGroupBy), 'metric' => 'count', 'op' => $this->limitOp, 'value' => $this->limitValue];
        if ($match !== null) {
            $def['match'] = $match;
        }

        return $def;
    }

    /**
     * Сложить условие из строк: один лист — сам по себе, несколько — под all/any.
     *
     * @param  array<int, array{field: string, op: string, value: mixed}>  $conditions
     * @return array<string, mixed>|null
     */
    private function buildCondition(string $combinator, array $conditions): ?array
    {
        $leaves = [];
        foreach ($conditions as $c) {
            if (($c['field'] ?? '') === '') {
                continue;
            }
            $leaves[] = [
                'field' => $c['field'],
                'op' => $c['op'] ?? '=',
                'value' => $this->coerceValue($c),
            ];
        }

        if ($leaves === []) {
            return null;
        }
        if (count($leaves) === 1) {
            return $leaves[0];
        }

        return [in_array($combinator, ['all', 'any'], true) ? $combinator : 'all' => $leaves];
    }

    /**
     * Привести значение строки условия к нужному типу.
     *
     * @param  array{field: string, op: string, value: mixed}  $c
     */
    private function coerceValue(array $c): mixed
    {
        $field = $c['field'];
        $op = $c['op'] ?? '=';
        $value = $c['value'] ?? null;

        if ($field === 'weekday') {
            return array_values(array_map('intval', (array) $value));
        }
        if (in_array($op, ['in', 'not_in'], true)) {
            $parts = is_array($value) ? $value : array_filter(array_map('trim', explode(',', (string) $value)), fn ($v) => $v !== '');

            return $this->isNumericField($field) ? array_values(array_map('intval', $parts)) : array_values($parts);
        }
        if ($this->isNumericField($field)) {
            return (int) $value;
        }

        return (string) $value;
    }

    /**
     * Разобрать definition в состояние конструктора. Если структура слишком сложная —
     * остаёмся в расширенном режиме с исходным JSON.
     *
     * @param  array<string, mixed>  $def
     */
    private function loadIntoBuilder(array $def): void
    {
        $this->type = in_array($def['type'] ?? null, ['forbid', 'require', 'limit'], true) ? $def['type'] : 'forbid';

        $matchParsed = $this->parseCondition($def['match'] ?? null);
        $requireParsed = $this->type === 'require' ? $this->parseCondition($def['require'] ?? null) : ['all', []];

        $matchOk = $matchParsed !== null || ! isset($def['match']);
        if ($matchParsed === null && ($this->type === 'forbid' || $this->type === 'require')) {
            $matchOk = false;
        }

        if (! $matchOk || $requireParsed === null) {
            // Не раскладывается на простые строки — показываем как JSON.
            $this->advanced = true;
            $this->definitionJson = json_encode($def, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            return;
        }

        [$this->matchCombinator, $this->matchConditions] = $matchParsed ?? ['all', []];
        [$this->requireCombinator, $this->requireConditions] = $requireParsed;

        if ($this->type === 'limit') {
            $this->limitGroupBy = is_array($def['group_by'] ?? null) ? $def['group_by'] : [];
            $this->limitOp = $def['op'] ?? '<=';
            $this->limitValue = (int) ($def['value'] ?? 0);
        }

        if ($this->matchConditions === []) {
            $this->matchConditions = [$this->blankCondition('weekday')];
        }
    }

    /**
     * Разложить условие на [комбинатор, строки]. Возвращает null, если есть вложенность/not.
     *
     * @return array{0: string, 1: array<int, array{field: string, op: string, value: mixed}>}|null
     */
    private function parseCondition(mixed $cond): ?array
    {
        if (! is_array($cond)) {
            return ['all', []];
        }

        if (isset($cond['field'])) {
            return ['all', [$this->leafToRow($cond)]];
        }

        foreach (['all', 'any'] as $combinator) {
            if (isset($cond[$combinator]) && is_array($cond[$combinator])) {
                $rows = [];
                foreach ($cond[$combinator] as $sub) {
                    if (! is_array($sub) || ! isset($sub['field'])) {
                        return null; // вложенные/составные — не для простого конструктора
                    }
                    $rows[] = $this->leafToRow($sub);
                }

                return [$combinator, $rows];
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $leaf
     * @return array{field: string, op: string, value: mixed}
     */
    private function leafToRow(array $leaf): array
    {
        $field = (string) ($leaf['field'] ?? 'group_id');
        $value = $leaf['value'] ?? '';

        return [
            'field' => $field,
            'op' => (string) ($leaf['op'] ?? '='),
            'value' => $field === 'weekday'
                ? array_map('strval', (array) $value)
                : (is_array($value) ? implode(', ', $value) : (string) $value),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function parseJsonDefinition(): ?array
    {
        $decoded = json_decode($this->definitionJson, true);
        if (! is_array($decoded) || json_last_error() !== JSON_ERROR_NONE) {
            $this->addError('definition', 'Некорректный JSON: '.json_last_error_msg());

            return null;
        }

        return $decoded;
    }

    // ── Вспомогательное ──────────────────────────────────────────────────────

    /**
     * @return array{field: string, op: string, value: mixed}
     */
    private function blankCondition(string $field): array
    {
        return [
            'field' => $field,
            'op' => $this->defaultOp($field),
            'value' => $this->isMultiValueField($field) ? [] : '',
        ];
    }

    private function defaultOp(string $field): string
    {
        return $field === 'weekday' ? 'in' : '=';
    }

    private function isMultiValueField(string $field): bool
    {
        return $field === 'weekday';
    }

    private function isNumericField(string $field): bool
    {
        return in_array($field, ['group_id', 'teacher_id', 'room_id', 'building_id', 'room_type_id', 'discipline_id', 'lesson_number', 'weekday', 'course', 'shift'], true);
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->description = '';
        $this->scope = 'global';
        $this->scopeId = null;
        $this->severity = 'soft';
        $this->isEnabled = true;
        $this->type = 'forbid';
        $this->matchCombinator = 'all';
        $this->matchConditions = [];
        $this->requireCombinator = 'all';
        $this->requireConditions = [];
        $this->limitGroupBy = ['teacher_id', 'date'];
        $this->limitOp = '<=';
        $this->limitValue = 4;
        $this->advanced = false;
        $this->definitionJson = '';
        $this->resetErrorBag();
    }

    /**
     * Проверяет структуру definition. Возвращает список ошибок (пустой — всё ок).
     *
     * @param  array<string, mixed>  $def
     * @return array<int, string>
     */
    private function validateDefinition(array $def): array
    {
        $errors = [];
        $type = $def['type'] ?? null;

        if (! in_array($type, ['forbid', 'require', 'limit'], true)) {
            return ['Поле "type" должно быть forbid, require или limit.'];
        }

        if (isset($def['match'])) {
            $errors = array_merge($errors, $this->validateCondition($def['match'], 'match'));
        }

        if ($type === 'require') {
            if (! isset($def['require'])) {
                $errors[] = 'Для привязки нужно условие «обязательно».';
            } else {
                $errors = array_merge($errors, $this->validateCondition($def['require'], 'require'));
            }
        }

        if ($type === 'limit') {
            $groupBy = $def['group_by'] ?? null;
            if (! is_array($groupBy) || $groupBy === []) {
                $errors[] = 'Для лимита нужно непустое поле группировки.';
            } else {
                foreach ($groupBy as $f) {
                    if (! in_array($f, self::FIELDS, true)) {
                        $errors[] = "Неизвестное поле группировки: {$f}.";
                    }
                }
            }
            if (! in_array($def['op'] ?? null, self::OPS, true)) {
                $errors[] = 'Для лимита нужен оператор.';
            }
            if (! is_numeric($def['value'] ?? null)) {
                $errors[] = 'Для лимита нужно числовое значение.';
            }
        }

        return $errors;
    }

    /**
     * @return array<int, string>
     */
    private function validateCondition(mixed $cond, string $path): array
    {
        if (! is_array($cond)) {
            return ["Условие в \"{$path}\" должно быть объектом."];
        }

        foreach (['all', 'any'] as $combinator) {
            if (isset($cond[$combinator])) {
                if (! is_array($cond[$combinator]) || $cond[$combinator] === []) {
                    return ["\"{$path}.{$combinator}\" должно быть непустым массивом условий."];
                }
                $errors = [];
                foreach ($cond[$combinator] as $i => $sub) {
                    $errors = array_merge($errors, $this->validateCondition($sub, "{$path}.{$combinator}[{$i}]"));
                }

                return $errors;
            }
        }

        if (isset($cond['not'])) {
            return $this->validateCondition($cond['not'], "{$path}.not");
        }

        if (isset($cond['field'])) {
            $errors = [];
            if (! in_array($cond['field'], self::FIELDS, true)) {
                $errors[] = "Неизвестное поле \"{$cond['field']}\".";
            }
            if (! in_array($cond['op'] ?? '=', self::OPS, true)) {
                $errors[] = "Неизвестный оператор в \"{$path}\".";
            }
            if (! array_key_exists('value', $cond)) {
                $errors[] = "В \"{$path}\" нет значения.";
            }

            return $errors;
        }

        return ["В \"{$path}\" нужно условие: all / any / not / поле."];
    }

    public function render()
    {
        return view('livewire.admin.custom-rules', [
            'rules' => CustomSchedulingRule::orderBy('name')->get(),
            'groups' => Group::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'teachers' => Teacher::query()->orderBy('last_name')->get()->map(fn ($t) => ['id' => $t->id, 'label' => $t->short_name]),
            'disciplines' => CurriculumDiscipline::orderBy('name')->get(['id', 'name', 'code'])
                ->map(fn ($d) => ['id' => $d->id, 'label' => trim(($d->code ? $d->code.' — ' : '').$d->name)]),
            'buildings' => Building::orderBy('name')->get(['id', 'name', 'short_name'])
                ->map(fn ($b) => ['id' => $b->id, 'label' => $b->short_name ?: $b->name]),
            'rooms' => Room::orderBy('number')->get(['id', 'number', 'name'])
                ->map(fn ($r) => ['id' => $r->id, 'label' => $r->number ?: $r->name]),
            'roomTypes' => RoomType::orderBy('name')->get(['id', 'name'])
                ->map(fn ($rt) => ['id' => $rt->id, 'label' => $rt->name]),
            'lessonTypes' => LessonType::orderBy('name')->get(['code', 'name'])
                ->map(fn ($lt) => ['code' => $lt->code, 'label' => $lt->name]),
            'fieldLabels' => self::FIELD_LABELS,
            'fields' => self::FIELDS,
        ]);
    }
}
