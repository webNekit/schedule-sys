<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Building;
use App\Models\CurriculumDiscipline;
use App\Models\CustomSchedulingRule;
use App\Models\RoomType;
use App\Services\Schedule\SchedulingRuleResolver;
use Illuminate\Database\Seeder;

/**
 * Демонстрационные авторские правила — по одному на каждую возможность движка.
 *
 * Намеренно НЕ пересекаются со встроенными «правилами генерации»
 * ({@see SchedulingRuleResolver}). Встроенные уже отвечают за:
 * окна, один корпус в день, вместимость, привязку преподавателя к дисциплине,
 * минимум пар, недельную перегрузку (в часах), физкультуру (сдвоенность), выбор дисциплины.
 *
 * Эти правила покрывают то, чего среди встроенных НЕТ:
 *  - дневной МАКСИМУМ пар у преподавателя (встроен только минимум);
 *  - не повторять одну дисциплину дважды в день у группы;
 *  - привязки «дисциплина → корпус» (по идентичности, а не по вместимости);
 *  - запреты по дисциплине/дню недели и составная логика И/ИЛИ.
 *
 * Идемпотентно: правила обновляются по названию. Привязки создаются только если
 * соответствующие дисциплина/корпус есть в базе.
 */
class CustomSchedulingRulesSeeder extends Seeder
{
    public function run(): void
    {
        // 1. ЛИМИТ: у преподавателя не больше 6 пар в день (встроен только минимум).
        $this->upsert('Преподаватель: не более 6 пар в день', [
            'scope' => 'global',
            'severity' => 'soft',
            'description' => 'Чтобы не перегружать преподавателя за один день.',
            'definition' => [
                'type' => 'limit',
                'group_by' => ['teacher_id', 'date'],
                'metric' => 'count',
                'op' => '<=',
                'value' => 6,
            ],
        ]);

        // 2. ЛИМИТ: одна дисциплина у группы — не чаще 1 раза в день.
        $this->upsert('Не повторять дисциплину дважды в день', [
            'scope' => 'global',
            'severity' => 'soft',
            'description' => 'Одна и та же дисциплина не должна стоять у группы дважды за день.',
            'definition' => [
                'type' => 'limit',
                'group_by' => ['group_id', 'discipline_id', 'date'],
                'metric' => 'count',
                'op' => '<=',
                'value' => 1,
            ],
        ]);

        // 3. СЛОЖНАЯ ЛОГИКА (запрет): 1 курс — нельзя в субботу ИЛИ позже 6-й пары.
        $this->upsert('1 курс: не суббота и не позже 6-й пары', [
            'scope' => 'course',
            'scope_id' => 1,
            'severity' => 'soft',
            'description' => 'Демонстрация составного условия (ИЛИ) с областью «по курсу».',
            'definition' => [
                'type' => 'forbid',
                'match' => ['any' => [
                    ['field' => 'weekday', 'op' => 'in', 'value' => [6]],
                    ['field' => 'lesson_number', 'op' => '>', 'value' => 6],
                ]],
            ],
        ]);

        // 4. ЗАПРЕТ по дисциплине и дню: конкретная дисциплина не ставится в понедельник.
        $discipline = CurriculumDiscipline::query()->orderBy('id')->skip(3)->first()
            ?? CurriculumDiscipline::query()->orderBy('id')->first();
        if ($discipline) {
            $this->upsert("«{$discipline->name}» не в понедельник", [
                'scope' => 'global',
                'severity' => 'soft',
                'description' => 'Пример запрета конкретной дисциплины в определённый день недели.',
                'definition' => [
                    'type' => 'forbid',
                    'match' => ['all' => [
                        ['field' => 'discipline_id', 'op' => '=', 'value' => $discipline->id],
                        ['field' => 'weekday', 'op' => 'in', 'value' => [1]],
                    ]],
                ],
            ]);
        }

        // 5. ПРИВЯЗКА (жёсткая, учитывается при генерации): дисциплина — только в одном корпусе.
        $bindDiscipline = CurriculumDiscipline::query()->orderBy('id')->first();
        $mainBuilding = Building::query()->where('name', 'like', '%лавн%')->first()
            ?? Building::query()->orderBy('id')->first();
        if ($bindDiscipline && $mainBuilding) {
            $this->upsert("«{$bindDiscipline->name}» только в «{$mainBuilding->name}»", [
                'scope' => 'global',
                'severity' => 'hard',
                'description' => 'Жёсткая привязка дисциплины к корпусу — генератор не поставит её в другой корпус.',
                'definition' => [
                    'type' => 'require',
                    'match' => ['field' => 'discipline_id', 'op' => '=', 'value' => $bindDiscipline->id],
                    'require' => ['field' => 'building_id', 'op' => '=', 'value' => $mainBuilding->id],
                ],
            ]);
        }

        // 6. ПРИВЯЗКА: физкультура — в любом спортивном зале (Спорт.комплекс ИЛИ Спорт.зал).
        // Привязка по ТИПУ аудитории, а не по корпусу: зал может быть в разных корпусах.
        $pe = CurriculumDiscipline::query()->where('name', 'like', '%изическ%')->first();
        $sportTypeIds = RoomType::query()->where('name', 'like', '%порт%')->pluck('id')->all();
        if ($pe && $sportTypeIds !== []) {
            // Если залов несколько — объединяем условия через «Любое из» (ИЛИ).
            $require = count($sportTypeIds) === 1
                ? ['field' => 'room_type_id', 'op' => '=', 'value' => $sportTypeIds[0]]
                : ['any' => array_map(
                    fn (int $id) => ['field' => 'room_type_id', 'op' => '=', 'value' => $id],
                    $sportTypeIds,
                )];

            $this->upsert('Физкультура — только в спортивном зале', [
                'scope' => 'global',
                'severity' => 'soft',
                'description' => 'Физра допускается в любом спортзале или спорткомплексе (по типу аудитории).',
                'definition' => [
                    'type' => 'require',
                    'match' => ['field' => 'discipline_id', 'op' => '=', 'value' => $pe->id],
                    'require' => $require,
                ],
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function upsert(string $name, array $attributes): void
    {
        CustomSchedulingRule::updateOrCreate(
            ['name' => $name],
            array_merge(['is_enabled' => true, 'scope_id' => null], $attributes),
        );
    }
}
