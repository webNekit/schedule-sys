<?php

declare(strict_types=1);

use App\Models\AcademicYear;
use App\Models\Department;
use App\Models\Group;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\ScheduleLesson;
use App\Models\Specialty;
use App\Models\SportComplexSlot;
use App\Services\Schedule\ConflictCheckerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;

uses(RefreshDatabase::class);

/**
 * In-memory пара с группой и типом аудитории (для проверки резерва спорткомплекса).
 */
function sportLesson(int $lessonNumber, string $date, string $roomTypeName): ScheduleLesson
{
    $lesson = (new ScheduleLesson)->forceFill([
        'id' => random_int(1, 1_000_000),
        'group_id' => 1,
        'discipline_id' => 5,
        'lesson_number' => $lessonNumber,
        'date' => $date,
    ]);
    $lesson->setRelation('group', (new Group)->forceFill(['id' => 1, 'name' => 'ИСП-1-22']));
    $room = (new Room)->forceFill(['id' => 1]);
    $room->setRelation('roomType', (new RoomType)->forceFill(['name' => $roomTypeName]));
    $lesson->setRelation('room', $room);

    return $lesson;
}

/** @param  array<int, ScheduleLesson>  $lessons */
function checkReservation(array $lessons): array
{
    $service = app(ConflictCheckerService::class);
    $conflicts = [];
    $ref = new ReflectionMethod($service, 'checkSportComplexReservation');
    $ref->setAccessible(true);
    $args = [new Collection($lessons), 1, &$conflicts];
    $ref->invokeArgs($service, $args);

    return $conflicts;
}

beforeEach(function () {
    $year = AcademicYear::create([
        'name' => '2025/2026', 'year_start' => 2025, 'year_end' => 2026,
        'date_start' => '2025-09-01', 'date_end' => '2026-08-31', 'is_current' => true,
    ]);
    $department = Department::create(['name' => 'Кафедра', 'short_name' => 'К']);
    $specialty = Specialty::create([
        'name' => 'Специальность', 'short_name' => 'С', 'code' => '09.02.07',
        'department_id' => $department->id, 'max_courses' => 4, 'is_active' => true,
    ]);
    Group::create([
        'name' => 'ИСП-1-22', 'academic_year_id' => $year->id, 'specialty_id' => $specialty->id,
        'department_id' => $department->id, 'current_course' => 1, 'status' => 'active', 'is_active' => true,
    ]);

    // Группа 1 ездит в спорткомплекс в понедельник (weekday 1) на 1-ю пару.
    SportComplexSlot::create(['group_id' => 1, 'weekday' => 1, 'lesson_number' => 1]);
});

it('flags a regular lesson on a reserved sport-complex pair', function () {
    // 2026-06-29 — понедельник.
    $conflicts = checkReservation([
        sportLesson(1, '2026-06-29', 'Аудитория'), // обычное занятие на зарезервированной паре
    ]);

    expect($conflicts)->toHaveCount(1)
        ->and($conflicts[0]['conflict_type'])->toBe('sport_complex_reserved')
        ->and($conflicts[0]['severity'])->toBe('error')
        ->and($conflicts[0]['lesson_number'])->toBe(1);
});

it('allows a sport-complex lesson on the reserved pair', function () {
    $conflicts = checkReservation([
        sportLesson(1, '2026-06-29', 'Спорт.комплекс'),
    ]);

    expect($conflicts)->toBeEmpty();
});

it('does not touch pairs that are not reserved', function () {
    $conflicts = checkReservation([
        sportLesson(2, '2026-06-29', 'Аудитория'), // 2-я пара не зарезервирована
    ]);

    expect($conflicts)->toBeEmpty();
});

it('does not flag on a different weekday', function () {
    // 2026-06-30 — вторник, резерва нет.
    $conflicts = checkReservation([
        sportLesson(1, '2026-06-30', 'Аудитория'),
    ]);

    expect($conflicts)->toBeEmpty();
});

it('flags physical education placed after the 4th pair', function () {
    // Физра на 5-й паре — запрещено (только 1–4).
    $conflicts = checkReservation([
        sportLesson(5, '2026-06-30', 'Спорт.комплекс'),
    ]);

    expect($conflicts)->toHaveCount(1)
        ->and($conflicts[0]['conflict_type'])->toBe('pe_after_fourth_pair')
        ->and($conflicts[0]['severity'])->toBe('error');
});

it('allows physical education on the 4th pair', function () {
    $conflicts = checkReservation([
        sportLesson(4, '2026-06-30', 'Спорт.комплекс'),
    ]);

    expect($conflicts)->toBeEmpty();
});
