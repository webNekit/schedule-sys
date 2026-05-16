<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Building;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Group;
use App\Models\LessonType;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Specialty;
use App\Models\SystemSetting;
use App\Models\Teacher;
use App\Models\TeacherPosition;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestEnvironmentSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Учебный год
        $academicYear = AcademicYear::firstOrCreate(
            ['is_current' => true],
            [
                'name' => '2026-2027',
                'year_start' => 2026,
                'year_end' => 2027,
                'date_start' => '2026-09-01',
                'date_end' => '2027-06-30',
                'first_semester_start' => '2026-09-01',
                'first_semester_end' => '2026-12-31',
                'second_semester_start' => '2027-01-11',
                'second_semester_end' => '2027-06-30',
            ]
        );

        // 2. Здание и Аудитории
        $building = Building::firstOrCreate(
            ['name' => 'Главный корпус'],
            ['short_name' => 'ГК', 'address' => 'ул. Программистов, 1', 'is_active' => true]
        );

        $typeLecture = RoomType::firstOrCreate(['name' => 'Учебная аудитория']);
        $typeLab = RoomType::firstOrCreate(['name' => 'Компьютерный класс']); // Исправлено: убрали requires_lab
        $typeSport = RoomType::firstOrCreate(['name' => 'Спортивный зал']);

        Room::firstOrCreate(['number' => '101', 'building_id' => $building->id], ['room_type_id' => $typeLecture->id, 'capacity' => 30, 'is_active' => true, 'is_available_for_booking' => true]);
        Room::firstOrCreate(['number' => '102', 'building_id' => $building->id], ['room_type_id' => $typeLecture->id, 'capacity' => 30, 'is_active' => true, 'is_available_for_booking' => true]);
        Room::firstOrCreate(['number' => '201', 'building_id' => $building->id], ['room_type_id' => $typeLab->id, 'capacity' => 15, 'is_active' => true, 'is_available_for_booking' => true]);
        Room::firstOrCreate(['number' => 'Спортзал 1', 'building_id' => $building->id], ['room_type_id' => $typeSport->id, 'capacity' => 60, 'is_active' => true, 'is_available_for_booking' => true]);

        // 3. Типы занятий
        LessonType::firstOrCreate(['code' => 'lecture'], ['name' => 'Лекция', 'short_name' => 'Лек']);
        LessonType::firstOrCreate(['code' => 'practice'], ['name' => 'Практика', 'short_name' => 'Пр', 'requires_lab' => true]);

        // 4. Структура (Кафедра, Специальность)
        $faculty = Faculty::firstOrCreate(['name' => 'Информационные технологии'], ['is_active' => true]);
        $department = Department::firstOrCreate(['name' => 'Кафедра программирования'], ['faculty_id' => $faculty->id, 'is_active' => true]);
        $specialty = Specialty::firstOrCreate(['code' => '09.02.07'], ['department_id' => $department->id, 'name' => 'Информационные системы и программирование', 'study_years' => 3, 'study_months' => 10, 'is_active' => true]);

        // 5. Настройки системы (Настройки для рабочих дней курсов)
        SystemSetting::updateOrCreate(['key' => 'working_days_course_1_2'], ['value' => json_encode([1, 2, 3, 4, 5])]);
        SystemSetting::updateOrCreate(['key' => 'working_days_course_3_4'], ['value' => json_encode([2, 3, 4, 5, 6])]);

        // Массивы доступных слотов для генератора (1 курс учится утром, 3 курс учится во вторую смену)
        $slotsCourse1 = [1 => [1, 2, 3, 4, 5], 2 => [1, 2, 3, 4, 5], 3 => [1, 2, 3, 4, 5], 4 => [1, 2, 3, 4, 5], 5 => [1, 2, 3, 4, 5]];
        $slotsCourse3 = [2 => [3, 4, 5, 6, 7], 3 => [3, 4, 5, 6, 7], 4 => [3, 4, 5, 6, 7], 5 => [3, 4, 5, 6, 7], 6 => [1, 2, 3, 4, 5]];

        SystemSetting::updateOrCreate(['key' => 'lesson_numbers_course_1'], ['value' => json_encode($slotsCourse1)]);
        SystemSetting::updateOrCreate(['key' => 'lesson_numbers_course_3'], ['value' => json_encode($slotsCourse3)]);

        // 6. Группы
        $group1 = Group::firstOrCreate(
            ['name' => 'ИСП-261'],
            ['specialty_id' => $specialty->id, 'department_id' => $department->id, 'academic_year_id' => $academicYear->id, 'current_course' => 1, 'shift' => 1, 'students_count' => 25, 'is_active' => true]
        );
        $group3 = Group::firstOrCreate(
            ['name' => 'ИСП-241'],
            ['specialty_id' => $specialty->id, 'department_id' => $department->id, 'academic_year_id' => $academicYear->id, 'current_course' => 3, 'shift' => 2, 'students_count' => 22, 'is_active' => true]
        );

        // Привяжем группы к главному корпусу
        $group1->buildings()->syncWithoutDetaching([$building->id => ['is_primary' => true]]);
        $group3->buildings()->syncWithoutDetaching([$building->id => ['is_primary' => true]]);

        // 7. Преподаватели
        $position = TeacherPosition::firstOrCreate(['name' => 'Преподаватель'], ['is_active' => true]);

        // Преподаватель 1: Стандартная пятидневка, пары 1-5
        $user1 = User::firstOrCreate(['email' => 'ivanov@college.ru'], ['name' => 'Иванов И.И.', 'password' => Hash::make('password'), 'department_id' => $department->id, 'is_active' => true]);
        Teacher::firstOrCreate(['user_id' => $user1->id], [
            'department_id' => $department->id,
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

        // Преподаватель 2: Совместитель, только ВТ и ЧТ, только со 2-й смены (пары 3-7)
        $user2 = User::firstOrCreate(['email' => 'petrov@college.ru'], ['name' => 'Петров П.П.', 'password' => Hash::make('password'), 'department_id' => $department->id, 'is_active' => true]);
        Teacher::firstOrCreate(['user_id' => $user2->id], [
            'department_id' => $department->id,
            'position_id' => $position->id,
            'last_name' => 'Петров',
            'first_name' => 'Петр',
            'middle_name' => 'Петрович',
            'rate' => 0.5,
            'max_hours_per_week' => 18,
            'max_lessons_per_day' => 4,
            'working_days' => [2, 4], // Вторник, Четверг
            'working_lesson_numbers' => [3, 4, 5, 6, 7], // Только вторая половина дня
            'is_active' => true,
        ]);

        // Преподаватель 3: Физрук (ПН, СР, ПТ)
        $user3 = User::firstOrCreate(['email' => 'sidorov@college.ru'], ['name' => 'Сидоров С.С.', 'password' => Hash::make('password'), 'department_id' => $department->id, 'is_active' => true]);
        Teacher::firstOrCreate(['user_id' => $user3->id], [
            'department_id' => $department->id,
            'position_id' => $position->id,
            'last_name' => 'Сидоров',
            'first_name' => 'Семен',
            'middle_name' => 'Семенович',
            'rate' => 1.0,
            'max_hours_per_week' => 36,
            'max_lessons_per_day' => 6,
            'working_days' => [1, 3, 5],
            'working_lesson_numbers' => [1, 2, 3, 4, 5, 6],
            'is_active' => true,
        ]);
    }
}
