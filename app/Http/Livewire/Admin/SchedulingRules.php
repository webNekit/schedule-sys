<?php

declare(strict_types=1);

namespace App\Http\Livewire\Admin;

use App\Models\Group;
use App\Models\SchedulingRule;
use App\Models\SystemSetting;
use App\Services\Schedule\SchedulingRuleResolver;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class SchedulingRules extends Component
{
    /** Уровень: global | course | group */
    public string $scope = 'global';

    public ?int $scopeId = null;

    /** @var array<string, array{is_enabled: bool, severity: ?string, params: array<string, mixed>, overridden: bool}> */
    public array $rows = [];

    public function mount(): void
    {
        $this->loadRules();
    }

    public function updatedScope(): void
    {
        // При переходе на глобальный уровень сбрасываем выбор курса/группы.
        if ($this->scope === 'global') {
            $this->scopeId = null;
        } elseif ($this->scope === 'course') {
            $this->scopeId = 1;
        } else {
            $this->scopeId = Group::where('is_active', true)->orderBy('name')->value('id');
        }
        $this->loadRules();
    }

    public function updatedScopeId(): void
    {
        $this->scopeId = $this->scopeId !== null ? (int) $this->scopeId : null;
        $this->loadRules();
    }

    public function loadRules(): void
    {
        $this->rows = [];

        foreach (SchedulingRuleResolver::DEFAULTS as $key => $def) {
            $row = SchedulingRule::where('key', $key)
                ->where('scope', $this->scope)
                ->where('scope_id', $this->scopeId)
                ->first();

            $params = ($row && is_array($row->params)) ? $row->params : $def['params'];

            $paramView = [];
            foreach ($def['params'] as $pName => $pDefault) {
                $value = $params[$pName] ?? $pDefault;
                if (is_array($pDefault)) {
                    // Дни недели редактируются чекбоксами — храним как массив чисел.
                    $paramView[$pName] = $this->isDayCheckboxParam($key, $pName)
                        ? array_map('intval', (array) $value)
                        : implode(', ', (array) $value);
                } else {
                    $paramView[$pName] = $value;
                }
            }

            $this->rows[$key] = [
                'is_enabled' => $row ? (bool) $row->is_enabled : $def['is_enabled'],
                'severity' => $row ? $row->severity : $def['severity'],
                'params' => $paramView,
                'overridden' => (bool) $row,
            ];
        }
    }

    public function saveRule(string $key): void
    {
        $def = SchedulingRuleResolver::DEFAULTS[$key] ?? null;
        if (! $def) {
            return;
        }

        $params = [];
        foreach ($def['params'] as $pName => $pDefault) {
            $raw = $this->rows[$key]['params'][$pName] ?? $pDefault;

            if (is_array($pDefault)) {
                // $raw может быть уже массивом (чекбоксы дней) или строкой «через запятую».
                $parts = is_array($raw)
                    ? array_values($raw)
                    : array_values(array_filter(
                        array_map('trim', explode(',', (string) $raw)),
                        fn ($v) => $v !== '',
                    ));
                $intArray = ! empty($pDefault) && is_int($pDefault[array_key_first($pDefault)]);
                $params[$pName] = $intArray
                    ? array_values(array_unique(array_map('intval', $parts)))
                    : array_map('strval', $parts);
            } elseif (is_bool($pDefault)) {
                $params[$pName] = (bool) $raw;
            } elseif (is_int($pDefault)) {
                $params[$pName] = (int) $raw;
            } else {
                $params[$pName] = (string) $raw;
            }
        }

        SchedulingRule::updateOrCreate(
            ['key' => $key, 'scope' => $this->scope, 'scope_id' => $this->scopeId],
            [
                'is_enabled' => (bool) ($this->rows[$key]['is_enabled'] ?? true),
                'severity' => $this->isConstraint($key) ? ($this->rows[$key]['severity'] ?? 'soft') : null,
                'params' => $params,
            ],
        );

        $this->loadRules();
        session()->flash('message', 'Правило сохранено');
    }

    /**
     * Удалить переопределение на текущем уровне (значение вернётся к менее специфичному).
     * На глобальном уровне удаление запрещено — это база.
     */
    public function resetRule(string $key): void
    {
        if ($this->scope === 'global') {
            return;
        }

        SchedulingRule::where('key', $key)
            ->where('scope', $this->scope)
            ->where('scope_id', $this->scopeId)
            ->delete();

        $this->loadRules();
        session()->flash('message', 'Переопределение сброшено');
    }

    public function isConstraint(string $key): bool
    {
        return (SchedulingRuleResolver::DEFAULTS[$key]['severity'] ?? null) !== null;
    }

    /** Параметр «дни недели», который редактируется чекбоксами Пн–Вс. */
    public function isDayCheckboxParam(string $key, string $pName): bool
    {
        return $key === 'min_lessons_check_weekdays' && $pName === 'days';
    }

    /**
     * Диапазон пар по каждому курсу в каждый день недели —
     * берётся из «Настроек системы» (номера пар по курсам и дням).
     * Для каждого дня/курса возвращается «с какой по какую пару» (from–to) и их число.
     *
     * @return array<int, array<int, array{from: int, to: int, count: int}>> [деньНедели => [курс => диапазон]]
     */
    private function pairsPerDayByCourse(): array
    {
        $result = [];
        for ($day = 1; $day <= 7; $day++) {
            $result[$day] = [];
        }

        for ($course = 1; $course <= 4; $course++) {
            $raw = SystemSetting::where('key', 'lesson_numbers_course_'.$course)->value('value');
            $decoded = $raw ? json_decode($raw, true) : [];

            for ($day = 1; $day <= 7; $day++) {
                $slots = is_array($decoded) && isset($decoded[$day]) && is_array($decoded[$day])
                    ? array_map('intval', $decoded[$day])
                    : [];

                $result[$day][$course] = $slots === []
                    ? ['from' => 0, 'to' => 0, 'count' => 0]
                    : ['from' => min($slots), 'to' => max($slots), 'count' => count($slots)];
            }
        }

        return $result;
    }

    public function render()
    {
        $constraintKeys = [];
        $paramKeys = [];
        foreach (SchedulingRuleResolver::DEFAULTS as $key => $def) {
            // Скрытые правила управляются в другом месте (например, слоты пар — в «Настройках системы»).
            if (in_array($key, self::HIDDEN_KEYS, true)) {
                continue;
            }
            if ($def['severity'] !== null) {
                $constraintKeys[] = $key;
            } else {
                $paramKeys[] = $key;
            }
        }

        return view('livewire.admin.scheduling-rules', [
            'constraintKeys' => $constraintKeys,
            'paramKeys' => $paramKeys,
            'labels' => self::LABELS,
            'descriptions' => self::DESCRIPTIONS,
            'paramLabels' => self::PARAM_LABELS,
            'guide' => $this->buildGuide($constraintKeys, $paramKeys),
            'groups' => Group::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'dayLabels' => [1 => 'Пн', 2 => 'Вт', 3 => 'Ср', 4 => 'Чт', 5 => 'Пт', 6 => 'Сб', 7 => 'Вс'],
            'dayPairs' => $this->pairsPerDayByCourse(),
        ]);
    }

    /**
     * Собирает структуру для блока «Подробная инструкция»:
     * по каждому видимому правилу — что это, как заполнять и пояснения к полям.
     *
     * @return array{constraints: array<int, array>, params: array<int, array>}
     */
    private function buildGuide(array $constraintKeys, array $paramKeys): array
    {
        $map = fn (array $keys) => array_map(fn ($key) => [
            'title' => self::LABELS[$key] ?? $key,
            'what' => self::DESCRIPTIONS[$key] ?? '',
            'how' => self::GUIDE[$key]['how'] ?? '',
            'fields' => self::GUIDE[$key]['fields'] ?? [],
        ], $keys);

        return [
            'constraints' => $map($constraintKeys),
            'params' => $map($paramKeys),
        ];
    }

    /**
     * Правила, которые не показываем в этом интерфейсе, чтобы не дублировать другие экраны.
     * «Слоты по умолчанию» задаются в «Настройках системы» (номера пар по курсам и дням);
     * здесь это лишь внутренний fallback генератора.
     */
    public const HIDDEN_KEYS = ['default_slots'];

    /** Человекочитаемые подписи правил. */
    public const LABELS = [
        'pairs_per_day' => 'Пар в день (базовое/максимум)',
        'default_slots' => 'Слоты по умолчанию (1-я/2-я смена)',
        'discipline_ranking' => 'Веса выбора дисциплины',
        'lesson_type_order' => 'Порядок типов занятий',
        'pe_block' => 'Блок физкультуры',
        'building_rotation' => 'Распределение корпусов',
        'min_lessons_check_weekdays' => 'Дни с обязательной проверкой минимума пар',
        'group_no_windows' => 'Без окон у группы',
        'teacher_no_windows' => 'Без окон у преподавателя',
        'one_building_per_day_group' => 'Один корпус в день (группа)',
        'one_building_per_day_teacher' => 'Один корпус в день (преподаватель)',
        'teacher_discipline_match' => 'Преподаватель привязан к дисциплине',
        'room_capacity' => 'Вместимость аудитории',
        'group_min_lessons' => 'Минимум пар у группы',
        'teacher_min_lessons' => 'Минимум пар у преподавателя',
        'group_weekly_overload' => 'Недельная перегрузка группы',
        'teacher_weekly_overload' => 'Недельная перегрузка преподавателя',
        'pe_grouping' => 'Физкультура сдвоена',
    ];

    /** Пояснения к правилам простым языком. */
    public const DESCRIPTIONS = [
        'pairs_per_day' => 'Сколько пар в день генератор ставит группе.',
        'default_slots' => 'Какие номера пар доступны, если для дня недели они не заданы отдельно.',
        'discipline_ranking' => 'Как генератор выбирает, какую дисциплину поставить следующей.',
        'lesson_type_order' => 'В каком порядке расходуются часы по типам занятий.',
        'pe_block' => 'Как ставится физкультура обычным группам (без выезда в спорткомплекс).',
        'building_rotation' => 'Как выбирается корпус, если у группы их несколько.',
        'min_lessons_check_weekdays' => 'В какие дни недели проверять минимальное число пар (обычно будни; короткую субботу можно не отмечать).',
        'group_no_windows' => 'Запрещает «окна» — свободные пары между занятиями у группы.',
        'teacher_no_windows' => 'Запрещает «окна» между парами у преподавателя.',
        'one_building_per_day_group' => 'Группа за один день учится только в одном корпусе.',
        'one_building_per_day_teacher' => 'Преподаватель за один день ведёт пары только в одном корпусе.',
        'teacher_discipline_match' => 'Преподаватель может вести только закреплённые за ним дисциплины.',
        'room_capacity' => 'Аудитория должна вмещать всех студентов группы.',
        'group_min_lessons' => 'Сколько минимум пар должно быть у группы в учебный день.',
        'teacher_min_lessons' => 'Сколько минимум пар должно быть у преподавателя в рабочий день.',
        'group_weekly_overload' => 'Предел недельной нагрузки группы в часах.',
        'teacher_weekly_overload' => 'Предел недельной нагрузки преподавателя (если у него не задан личный лимит).',
        'pe_grouping' => 'Физкультура должна стоять сдвоенным блоком, не разрываясь другими парами.',
    ];

    /** Подписи отдельных полей-параметров. */
    public const PARAM_LABELS = [
        'base' => 'Базовое число пар',
        'max' => 'Максимум пар',
        'shift1' => 'Пары 1-й смены',
        'shift2' => 'Пары 2-й смены',
        'low_hours_threshold' => 'Порог «малочасовой», ч',
        'low_hours_boost' => 'Приоритет малочасовым',
        'repeat_week_penalty' => 'Штраф за повтор в неделю',
        'max_per_week' => 'Максимум раз в неделю',
        'min_days_gap' => 'Минимум дней между занятиями',
        'recent_gap_penalty' => 'Штраф за частый повтор',
        'top_n_random' => 'Случайный выбор из топ-N',
        'order' => 'Порядок (через запятую)',
        'doubled' => 'Сдвоенная пара',
        'max_start_slot' => 'Не позже пары №',
        'strategy' => 'Стратегия',
        'days' => 'Дни проверки (1=Пн … 7=Вс)',
        'min' => 'Минимум пар',
        'max_hours' => 'Максимум часов в неделю',
    ];

    /**
     * Подробная инструкция по каждому правилу: как заполнять (how) и пояснения к полям (fields).
     *
     * @var array<string, array{how: string, fields?: array<string, string>}>
     */
    public const GUIDE = [
        // ── Ограничения ──────────────────────────────────────────────
        'group_no_windows' => [
            'how' => 'Оставьте «Включено» и «Ошибка», если у студентов не должно быть свободных пар между занятиями. Если в колледже окна допустимы — снимите галочку «Включено».',
        ],
        'teacher_no_windows' => [
            'how' => 'Обычно ставят «Предупреждение»: генератор старается избегать окон у преподавателя, но не считает их грубой ошибкой. Чтобы запретить строго — выберите «Ошибка».',
        ],
        'one_building_per_day_group' => [
            'how' => 'Включите, если корпуса далеко друг от друга и группе нельзя переезжать в течение дня. Если корпус один или переезды допустимы — можно выключить.',
        ],
        'one_building_per_day_teacher' => [
            'how' => 'То же для преподавателя: запрещает вести пары в один день в разных корпусах. Включайте при удалённых корпусах.',
        ],
        'teacher_discipline_match' => [
            'how' => 'Рекомендуется всегда держать «Включено» + «Ошибка»: преподавателю нельзя ставить дисциплину, которая за ним не закреплена.',
        ],
        'room_capacity' => [
            'how' => '«Ошибка» — если аудитория обязана вмещать всю группу. «Предупреждение» — если небольшое превышение вместимости допустимо.',
        ],
        'group_min_lessons' => [
            'how' => 'Укажите минимально допустимое число пар у группы в учебный день. Дни-исключения (например суббота) настраиваются в правиле «Дни без проверки минимума пар».',
            'fields' => ['min' => 'Сколько минимум пар должно быть в день. Обычно 3.'],
        ],
        'teacher_min_lessons' => [
            'how' => 'Минимальное число пар у преподавателя в рабочий день, чтобы он не приходил ради одной пары.',
            'fields' => ['min' => 'Минимум пар в день у преподавателя. Обычно 2.'],
        ],
        'group_weekly_overload' => [
            'how' => 'Предел недельной нагрузки группы. Если пар окажется больше — появится предупреждение о перегрузке.',
            'fields' => ['max_hours' => 'Максимум учебных часов в неделю. Обычно 36.'],
        ],
        'teacher_weekly_overload' => [
            'how' => 'Предел недельной нагрузки преподавателя. Применяется, только если у самого преподавателя не задан личный лимит часов в его карточке.',
            'fields' => ['max_hours' => 'Максимум часов в неделю по умолчанию. Обычно 36.'],
        ],
        'pe_grouping' => [
            'how' => 'Включите, если физкультуру ставят сдвоенным блоком (две пары подряд) и её нельзя разрывать другими занятиями.',
        ],

        // ── Параметры ────────────────────────────────────────────────
        'pairs_per_day' => [
            'how' => 'Задаёт, сколько пар в день генератор ставит группе. Недельная нагрузка распределяется по дням так, чтобы уложиться между базовым и максимальным числом.',
            'fields' => [
                'base' => 'Сколько пар ставить в обычный день. Обычно 3.',
                'max' => 'Потолок: больше этого числа пар в день не поставят. Обычно 5.',
            ],
        ],
        'discipline_ranking' => [
            'how' => 'Тонкая настройка того, как генератор выбирает следующую дисциплину. Менять стоит только если расписание получается несбалансированным — в обычной работе можно оставить значения по умолчанию.',
            'fields' => [
                'low_hours_threshold' => 'Если у дисциплины остаётся меньше или столько часов — её ставят в первую очередь, чтобы успеть выдать. Обычно 10.',
                'low_hours_boost' => 'Насколько сильно поднимать в приоритете «малочасовые». Большое число = ставить почти всегда первыми.',
                'repeat_week_penalty' => 'Штраф за повторную постановку дисциплины на той же неделе — чтобы она не шла слишком часто.',
                'max_per_week' => 'Ориентир, сколько раз в неделю ставить дисциплину.',
                'min_days_gap' => 'Желательный перерыв в днях между занятиями по одной дисциплине. Обычно 2.',
                'recent_gap_penalty' => 'Штраф, если дисциплину ставили совсем недавно (меньше указанного перерыва).',
                'top_n_random' => 'Из скольких лучших дисциплин выбирать случайно, чтобы расписание не было одинаковым каждый раз. Обычно 3.',
            ],
        ],
        'lesson_type_order' => [
            'how' => 'Порядок, в котором расходуются часы по типам занятий. Указывается кодами через запятую: lecture (лекция), practice (практика), lab (лабораторная).',
            'fields' => ['order' => 'Например: lecture, practice, lab — сначала лекции, потом практики, потом лабораторные.'],
        ],
        'pe_block' => [
            'how' => 'Как ставится физкультура обычным группам (которые не выезжают в спорткомплекс).',
            'fields' => [
                'doubled' => 'Ставить физкультуру сдвоенным блоком (две пары подряд). Снимите галочку для одиночных занятий.',
                'max_per_week' => 'Сколько блоков физкультуры в неделю. Обычно 1.',
                'max_start_slot' => 'До какой пары можно начинать физкультуру (чтобы она была ближе к началу дня). Обычно 3.',
            ],
        ],
        'building_rotation' => [
            'how' => 'Как выбрать корпус, если у группы их несколько.',
            'fields' => ['strategy' => '«Чередовать по дням недели» — в разные дни разные корпуса; «Только основной корпус» — всегда первый (основной) корпус группы.'],
        ],
        'min_lessons_check_weekdays' => [
            'how' => 'Отметьте галочкой дни, в которые НУЖНО проверять минимальное число пар (обычно будни). Снимите галочку с дня, где минимум не важен (например, короткая суббота). Под каждым днём показано, с какой по какую пару положено по курсам (1/2/3/4) согласно «Настройкам системы».',
        ],
    ];
}
