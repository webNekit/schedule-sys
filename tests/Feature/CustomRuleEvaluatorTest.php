<?php

declare(strict_types=1);

use App\Models\CustomSchedulingRule;
use App\Models\Group;
use App\Models\LessonType;
use App\Models\Room;
use App\Models\ScheduleLesson;
use App\Services\Schedule\CustomRuleEvaluator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;

uses(RefreshDatabase::class);

/**
 * Собирает in-memory пару (без записи в БД) с нужными для движка полями и связями.
 *
 * @param  array<string, mixed>  $attrs
 */
function makeLesson(array $attrs, int $course = 1, int $shift = 1, ?int $buildingId = null, ?string $type = null, ?int $roomTypeId = null): ScheduleLesson
{
    $lesson = (new ScheduleLesson)->forceFill([
        'id' => $attrs['id'] ?? random_int(1, 1_000_000),
        'group_id' => $attrs['group_id'] ?? 1,
        'teacher_id' => $attrs['teacher_id'] ?? null,
        'room_id' => $attrs['room_id'] ?? null,
        'discipline_id' => $attrs['discipline_id'] ?? null,
        'lesson_number' => $attrs['lesson_number'] ?? 1,
        'date' => $attrs['date'],
    ]);

    $lesson->setRelation('group', (new Group)->forceFill(['current_course' => $course, 'shift' => $shift]));
    $lesson->setRelation('room', ($buildingId === null && $roomTypeId === null) ? null : (new Room)->forceFill(['building_id' => $buildingId, 'room_type_id' => $roomTypeId]));
    $lesson->setRelation('lessonType', $type === null ? null : (new LessonType)->forceFill(['code' => $type]));

    return $lesson;
}

/** @param  array<int, ScheduleLesson>  $lessons */
function evaluateRules(array $lessons): array
{
    $conflicts = [];
    (new CustomRuleEvaluator)->evaluate(new Collection($lessons), 1, $conflicts);

    return $conflicts;
}

it('forbids lessons matching a weekday', function () {
    CustomSchedulingRule::create([
        'name' => 'Группа 1 не учится в субботу',
        'scope' => 'global', 'severity' => 'hard', 'is_enabled' => true,
        'definition' => [
            'type' => 'forbid',
            'match' => ['all' => [
                ['field' => 'group_id', 'op' => '=', 'value' => 1],
                ['field' => 'weekday', 'op' => 'in', 'value' => [6]],
            ]],
        ],
    ]);

    $conflicts = evaluateRules([
        makeLesson(['group_id' => 1, 'date' => '2026-07-04']), // суббота — нарушение
        makeLesson(['group_id' => 1, 'date' => '2026-06-29']), // понедельник — ок
    ]);

    expect($conflicts)->toHaveCount(1)
        ->and($conflicts[0]['severity'])->toBe('error')
        ->and($conflicts[0]['conflict_type'])->toBe('custom_rule')
        ->and($conflicts[0]['date'])->toBe('2026-07-04');
});

it('requires a binding condition (discipline must be in a building)', function () {
    CustomSchedulingRule::create([
        'name' => 'Дисциплина 10 только в корпусе 2',
        'scope' => 'global', 'severity' => 'soft', 'is_enabled' => true,
        'definition' => [
            'type' => 'require',
            'match' => ['field' => 'discipline_id', 'op' => '=', 'value' => 10],
            'require' => ['field' => 'building_id', 'op' => '=', 'value' => 2],
        ],
    ]);

    $conflicts = evaluateRules([
        makeLesson(['discipline_id' => 10, 'date' => '2026-06-29'], buildingId: 2), // ок
        makeLesson(['discipline_id' => 10, 'date' => '2026-06-29'], buildingId: 3), // нарушение
        makeLesson(['discipline_id' => 11, 'date' => '2026-06-29'], buildingId: 3), // не под правилом
    ]);

    expect($conflicts)->toHaveCount(1)
        ->and($conflicts[0]['severity'])->toBe('warning')
        ->and($conflicts[0]['discipline_id'])->toBe(10);
});

it('enforces an aggregate limit per group key', function () {
    CustomSchedulingRule::create([
        'name' => 'Преподаватель 7 — не более 1 пары в день',
        'scope' => 'global', 'severity' => 'hard', 'is_enabled' => true,
        'definition' => [
            'type' => 'limit',
            'match' => ['field' => 'teacher_id', 'op' => '=', 'value' => 7],
            'group_by' => ['teacher_id', 'date'],
            'metric' => 'count',
            'op' => '<=',
            'value' => 1,
        ],
    ]);

    $conflicts = evaluateRules([
        makeLesson(['teacher_id' => 7, 'date' => '2026-06-29', 'lesson_number' => 1]),
        makeLesson(['teacher_id' => 7, 'date' => '2026-06-29', 'lesson_number' => 2]), // 2 > 1 → нарушение
        makeLesson(['teacher_id' => 7, 'date' => '2026-06-30', 'lesson_number' => 1]), // другой день — ок
    ]);

    expect($conflicts)->toHaveCount(1)
        ->and($conflicts[0]['teacher_id'])->toBe(7)
        ->and($conflicts[0]['date'])->toBe('2026-06-29')
        ->and($conflicts[0]['lesson_number'])->toBeNull(); // агрегат не привязан к конкретной паре
});

it('supports nested any/not logic', function () {
    CustomSchedulingRule::create([
        'name' => 'Сложное условие',
        'scope' => 'global', 'severity' => 'soft', 'is_enabled' => true,
        'definition' => [
            'type' => 'forbid',
            // group 1 И (суббота ИЛИ пара > 4)
            'match' => ['all' => [
                ['field' => 'group_id', 'op' => '=', 'value' => 1],
                ['any' => [
                    ['field' => 'weekday', 'op' => 'in', 'value' => [6]],
                    ['field' => 'lesson_number', 'op' => '>', 'value' => 4],
                ]],
            ]],
        ],
    ]);

    $conflicts = evaluateRules([
        makeLesson(['group_id' => 1, 'date' => '2026-06-29', 'lesson_number' => 5]), // пара 5 → нарушение
        makeLesson(['group_id' => 1, 'date' => '2026-07-04', 'lesson_number' => 1]), // суббота → нарушение
        makeLesson(['group_id' => 1, 'date' => '2026-06-29', 'lesson_number' => 2]), // ок
        makeLesson(['group_id' => 2, 'date' => '2026-07-04', 'lesson_number' => 5]), // другая группа → ок
    ]);

    expect($conflicts)->toHaveCount(2);
});

it('applies course scope only to that course', function () {
    CustomSchedulingRule::create([
        'name' => 'Только 1 курс без субботы',
        'scope' => 'course', 'scope_id' => 1, 'severity' => 'soft', 'is_enabled' => true,
        'definition' => [
            'type' => 'forbid',
            'match' => ['field' => 'weekday', 'op' => 'in', 'value' => [6]],
        ],
    ]);

    $conflicts = evaluateRules([
        makeLesson(['group_id' => 1, 'date' => '2026-07-04'], course: 1), // 1 курс, суббота → нарушение
        makeLesson(['group_id' => 2, 'date' => '2026-07-04'], course: 3), // 3 курс → вне правила
    ]);

    expect($conflicts)->toHaveCount(1);
});

it('blocks placement violating a hard forbid rule', function () {
    CustomSchedulingRule::create([
        'name' => 'Нет субботы', 'scope' => 'global', 'severity' => 'hard', 'is_enabled' => true,
        'definition' => ['type' => 'forbid', 'match' => ['field' => 'weekday', 'op' => 'in', 'value' => [6]]],
    ]);

    $evaluator = new CustomRuleEvaluator;

    expect($evaluator->allowsPlacement(['group_id' => 1, 'course' => 1, 'weekday' => 6, 'date' => '2026-07-04']))->toBeFalse()
        ->and($evaluator->allowsPlacement(['group_id' => 1, 'course' => 1, 'weekday' => 1, 'date' => '2026-06-29']))->toBeTrue();
});

it('blocks placement violating a hard require (binding) rule', function () {
    CustomSchedulingRule::create([
        'name' => 'Дисц.10 → корпус 2', 'scope' => 'global', 'severity' => 'hard', 'is_enabled' => true,
        'definition' => [
            'type' => 'require',
            'match' => ['field' => 'discipline_id', 'op' => '=', 'value' => 10],
            'require' => ['field' => 'building_id', 'op' => '=', 'value' => 2],
        ],
    ]);

    $evaluator = new CustomRuleEvaluator;

    expect($evaluator->allowsPlacement(['group_id' => 1, 'course' => 1, 'discipline_id' => 10, 'building_id' => 3]))->toBeFalse()
        ->and($evaluator->allowsPlacement(['group_id' => 1, 'course' => 1, 'discipline_id' => 10, 'building_id' => 2]))->toBeTrue()
        ->and($evaluator->allowsPlacement(['group_id' => 1, 'course' => 1, 'discipline_id' => 11, 'building_id' => 3]))->toBeTrue();
});

it('does not enforce soft or limit rules at placement time', function () {
    CustomSchedulingRule::create([
        'name' => 'Мягкий запрет', 'scope' => 'global', 'severity' => 'soft', 'is_enabled' => true,
        'definition' => ['type' => 'forbid', 'match' => ['field' => 'weekday', 'op' => 'in', 'value' => [6]]],
    ]);
    CustomSchedulingRule::create([
        'name' => 'Жёсткий лимит', 'scope' => 'global', 'severity' => 'hard', 'is_enabled' => true,
        'definition' => ['type' => 'limit', 'group_by' => ['teacher_id', 'date'], 'op' => '<=', 'value' => 1],
    ]);

    $evaluator = new CustomRuleEvaluator;

    // Мягкое правило и агрегатный лимит не блокируют размещение на лету.
    expect($evaluator->allowsPlacement(['group_id' => 1, 'course' => 1, 'weekday' => 6, 'teacher_id' => 7, 'date' => '2026-07-04']))->toBeTrue();
});

it('allows a discipline in any of several room types (gym in complex OR gym hall)', function () {
    // Физра (дисциплина 9) обязана быть в типе 7 (Спорт.комплекс) ИЛИ 8 (Спорт.зал).
    CustomSchedulingRule::create([
        'name' => 'Физра — спортзал', 'scope' => 'global', 'severity' => 'soft', 'is_enabled' => true,
        'definition' => [
            'type' => 'require',
            'match' => ['field' => 'discipline_id', 'op' => '=', 'value' => 9],
            'require' => ['any' => [
                ['field' => 'room_type_id', 'op' => '=', 'value' => 7],
                ['field' => 'room_type_id', 'op' => '=', 'value' => 8],
            ]],
        ],
    ]);

    $conflicts = evaluateRules([
        makeLesson(['discipline_id' => 9, 'date' => '2026-06-29'], roomTypeId: 7), // спорткомплекс — ок
        makeLesson(['discipline_id' => 9, 'date' => '2026-06-29'], roomTypeId: 8), // спортзал — ок
        makeLesson(['discipline_id' => 9, 'date' => '2026-06-29'], roomTypeId: 6), // обычная аудитория — нарушение
    ]);

    expect($conflicts)->toHaveCount(1)
        ->and($conflicts[0]['discipline_id'])->toBe(9);
});

it('ignores disabled rules', function () {
    CustomSchedulingRule::create([
        'name' => 'Выключенное правило',
        'scope' => 'global', 'severity' => 'hard', 'is_enabled' => false,
        'definition' => ['type' => 'forbid', 'match' => ['field' => 'group_id', 'op' => '=', 'value' => 1]],
    ]);

    $conflicts = evaluateRules([
        makeLesson(['group_id' => 1, 'date' => '2026-06-29']),
    ]);

    expect($conflicts)->toBeEmpty();
});
