<?php

declare(strict_types=1);

use App\Models\AcademicYear;
use App\Models\Building;
use App\Models\CurriculumDiscipline;
use App\Models\CurriculumPlan;
use App\Models\CurriculumSemester;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Group;
use App\Models\HoursTracking;
use App\Models\LessonType;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\ScheduleLesson;
use App\Models\ScheduleVersion;
use App\Models\Specialty;
use App\Models\Teacher;
use App\Models\TeacherPosition;
use App\Models\User;

beforeEach(function () {
    // 1. Учебный год
    $this->academicYear = AcademicYear::create([
        'name' => '2026-2027',
        'year_start' => 2026,
        'year_end' => 2027,
        'date_start' => '2026-09-01',
        'date_end' => '2027-06-30',
        'first_semester_start' => '2026-09-01',
        'first_semester_end' => '2026-12-31',
        'second_semester_start' => '2027-01-11',
        'second_semester_end' => '2027-06-30',
        'is_current' => true,
    ]);

    // 2. Здание и Аудитории
    $this->building = Building::create([
        'name' => 'Главный корпус',
        'short_name' => 'ГК',
        'address' => 'ул. Программистов, 1',
        'is_active' => true,
    ]);

    $roomType = RoomType::create(['name' => 'Учебная аудитория']);
    $this->room = Room::create([
        'number' => '101',
        'building_id' => $this->building->id,
        'room_type_id' => $roomType->id,
        'capacity' => 30,
        'is_active' => true,
        'is_available_for_booking' => true,
    ]);

    // 3. Типы занятий
    $this->lessonType = LessonType::create(['code' => 'lecture', 'name' => 'Лекция', 'short_name' => 'Лек']);

    // 4. Структура (Кафедра, Специальность)
    $faculty = Faculty::create(['name' => 'Информационные технологии', 'is_active' => true]);
    $this->department = Department::create(['name' => 'Кафедра программирования', 'faculty_id' => $faculty->id, 'is_active' => true]);
    $specialty = Specialty::create([
        'code' => '09.02.07',
        'department_id' => $this->department->id,
        'name' => 'Информационные системы и программирование',
        'study_years' => 3,
        'study_months' => 10,
        'is_active' => true,
    ]);

    // 5. Группы
    $this->group = Group::create([
        'name' => 'ИСП-261',
        'specialty_id' => $specialty->id,
        'department_id' => $this->department->id,
        'academic_year_id' => $this->academicYear->id,
        'current_course' => 1,
        'shift' => 1,
        'students_count' => 25,
        'is_active' => true,
    ]);
    $this->group->buildings()->sync([$this->building->id => ['is_primary' => true]]);

    // 6. Преподаватели
    $position = TeacherPosition::create(['name' => 'Преподаватель', 'is_active' => true]);

    $user1 = User::create(['email' => 'teacher1@college.ru', 'name' => 'Иванов И.И.', 'password' => bcrypt('password'), 'department_id' => $this->department->id, 'is_active' => true]);
    $this->teacher1 = Teacher::create([
        'user_id' => $user1->id,
        'department_id' => $this->department->id,
        'position_id' => $position->id,
        'last_name' => 'Иванов',
        'first_name' => 'Иван',
        'middle_name' => 'Иванович',
        'rate' => 1.0,
        'max_hours_per_week' => 36,
        'max_lessons_per_day' => 5,
        'working_days' => [1, 2, 3, 4, 5],
        'working_lesson_numbers' => [1, 2, 3, 4, 5],
        'is_active' => true,
    ]);

    $user2 = User::create(['email' => 'teacher2@college.ru', 'name' => 'Петров П.П.', 'password' => bcrypt('password'), 'department_id' => $this->department->id, 'is_active' => true]);
    $this->teacher2 = Teacher::create([
        'user_id' => $user2->id,
        'department_id' => $this->department->id,
        'position_id' => $position->id,
        'last_name' => 'Петров',
        'first_name' => 'Петр',
        'middle_name' => 'Петрович',
        'rate' => 1.0,
        'max_hours_per_week' => 36,
        'max_lessons_per_day' => 5,
        'working_days' => [1, 2, 3, 4, 5],
        'working_lesson_numbers' => [1, 2, 3, 4, 5],
        'is_active' => true,
    ]);

    // 7. Учебный план и дисциплины
    $curriculumPlan = CurriculumPlan::create([
        'name' => 'План ИСП 2026-2027',
        'specialty_id' => $specialty->id,
        'academic_year_id' => $this->academicYear->id,
        'is_active' => true,
    ]);

    $this->discipline = CurriculumDiscipline::create([
        'curriculum_plan_id' => $curriculumPlan->id,
        'name' => 'Информатика',
        'code' => 'ОУД.03',
        'is_schedulable' => true,
    ]);

    $this->semester = CurriculumSemester::create([
        'discipline_id' => $this->discipline->id,
        'course_number' => 1,
        'semester_number' => 1,
        'semester_in_course' => 1,
        'hours_total' => 72,
    ]);

    // Пользователь для publish
    $this->publishUser = $user1;
});

// ============================================================
// Хелпер: создать версию расписания с уроком
// ============================================================
function createVersionWithLesson(array $overrides = []): array
{
    $data = array_merge([
        'teacher_id' => test()->teacher1->id,
        'status' => 'active',
    ], $overrides);

    $version = ScheduleVersion::create([
        'name' => 'Тестовое расписание',
        'status' => 'draft',
        'academic_year_id' => test()->academicYear->id,
        'department_id' => test()->department->id,
        'date_from' => '2026-09-07',
        'date_to' => '2026-09-13',
    ]);

    $lesson = ScheduleLesson::create([
        'version_id' => $version->id,
        'date' => '2026-09-10',
        'lesson_number' => 1,
        'shift' => 1,
        'group_id' => test()->group->id,
        'discipline_id' => test()->discipline->id,
        'lesson_type_id' => test()->lessonType->id,
        'teacher_id' => $data['teacher_id'],
        'room_id' => test()->room->id,
        'building_id' => test()->building->id,
        'status' => $data['status'],
    ]);

    return [$version, $lesson];
}

// ============================================================
// ТЕСТЫ
// ============================================================

it('tracks 2 hours per pair on schedule publication', function () {
    [$version, $lesson] = createVersionWithLesson();

    expect(HoursTracking::count())->toBe(0);

    $version->publish($this->publishUser->id);

    expect(HoursTracking::count())->toBe(1);

    $tracking = HoursTracking::first();
    expect((int) $tracking->teacher_id)->toBe($this->teacher1->id)
        ->and((float) $tracking->hours_conducted)->toBe(2.0)
        ->and((bool) $tracking->is_cancelled)->toBeFalse();
});

it('skips tracking when teacher_id is null during publication', function () {
    [$version, $lesson] = createVersionWithLesson(['teacher_id' => null]);

    $version->publish($this->publishUser->id);

    expect(HoursTracking::count())->toBe(0);
});

it('returns hours when teacher is removed from a published lesson', function () {
    [$version, $lesson] = createVersionWithLesson();
    $version->publish($this->publishUser->id);

    expect(HoursTracking::where('is_cancelled', false)->count())->toBe(1);

    // Диспетчер убирает преподавателя
    $lesson->update(['teacher_id' => null]);

    $tracking = HoursTracking::first();
    expect((bool) $tracking->is_cancelled)->toBeTrue()
        ->and($tracking->notes)->toBe('Замена или отмена');

    // Новая запись не должна быть создана (teacher = null)
    expect(HoursTracking::where('is_cancelled', false)->count())->toBe(0);
});

it('swaps hours when teacher is replaced in a published lesson', function () {
    [$version, $lesson] = createVersionWithLesson();
    $version->publish($this->publishUser->id);

    expect(HoursTracking::count())->toBe(1);

    // Замена teacher1 → teacher2
    $lesson->update(['teacher_id' => $this->teacher2->id]);

    expect(HoursTracking::count())->toBe(2);

    $old = HoursTracking::where('teacher_id', $this->teacher1->id)->first();
    expect((bool) $old->is_cancelled)->toBeTrue()
        ->and($old->notes)->toBe('Замена или отмена');

    $new = HoursTracking::where('teacher_id', $this->teacher2->id)->first();
    expect((bool) $new->is_cancelled)->toBeFalse()
        ->and((float) $new->hours_conducted)->toBe(2.0);
});

it('marks hours as cancelled when lesson is cancelled or deleted', function () {
    [$version, $lesson] = createVersionWithLesson();
    $version->publish($this->publishUser->id);

    expect(HoursTracking::where('is_cancelled', false)->count())->toBe(1);

    // Отмена занятия
    $lesson->update(['status' => 'cancelled']);

    expect((bool) HoursTracking::first()->is_cancelled)->toBeTrue()
        ->and(HoursTracking::first()->notes)->toBe('Замена или отмена');

    // Сбрасываем для теста удаления
    HoursTracking::query()->update(['is_cancelled' => false, 'notes' => null]);
    $lesson->update(['status' => 'active']);

    // Удаление занятия
    $lesson->delete();

    expect((bool) HoursTracking::first()->is_cancelled)->toBeTrue()
        ->and(HoursTracking::first()->notes)->toBe('Пара удалена');
});
