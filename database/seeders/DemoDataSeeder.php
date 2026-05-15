<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoDataSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        if (DB::table('buildings')->exists()) {
            return;
        }

        $building1Id = DB::table('buildings')->insertGetId([
            'name' => 'Корпус №1',
            'short_name' => 'Корпус 1',
            'address' => 'г. Москва, ул. Ленина, д. 10',
            'floors_count' => 4,
            'description' => 'Главный учебный корпус',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $building2Id = DB::table('buildings')->insertGetId([
            'name' => 'Корпус №2',
            'short_name' => 'Корпус 2',
            'address' => 'г. Москва, ул. Ленина, д. 12',
            'floors_count' => 3,
            'description' => 'Лабораторный корпус',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $roomTypes = DB::table('room_types')->pluck('id', 'name')->toArray();

        $roomsData = [];
        for ($i = 1; $i <= 10; $i++) {
            $floor = min($i, 4);
            $type = $i <= 3 ? 'Учебная аудитория' : ($i <= 5 ? 'Компьютерный класс' : ($i <= 7 ? 'Лаборатория' : 'Лекционный зал'));
            $roomsData[] = [
                'building_id' => $building1Id,
                'room_type_id' => $roomTypes[$type] ?? 1,
                'number' => '1-'.str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                'name' => 'Аудитория '.$i,
                'capacity' => 25 + ($i * 5),
                'floor' => $floor,
                'is_active' => true,
                'sort_order' => $i,
            ];
        }
        for ($i = 1; $i <= 10; $i++) {
            $floor = min($i, 3);
            $type = $i <= 3 ? 'Учебная аудитория' : ($i <= 5 ? 'Мастерская' : ($i <= 7 ? 'Лаборатория' : 'Спортивный зал'));
            $roomsData[] = [
                'building_id' => $building2Id,
                'room_type_id' => $roomTypes[$type] ?? 1,
                'number' => '2-'.str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                'name' => 'Аудитория '.(10 + $i),
                'capacity' => 20 + ($i * 3),
                'floor' => $floor,
                'is_active' => true,
                'sort_order' => 10 + $i,
            ];
        }
        DB::table('rooms')->insert($roomsData);

        $faculty1Id = DB::table('faculties')->insertGetId([
            'name' => 'Факультет информационных технологий',
            'short_name' => 'ФИТ',
            'head_name' => 'Иванов И.И.',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $faculty2Id = DB::table('faculties')->insertGetId([
            'name' => 'Факультет экономики и права',
            'short_name' => 'ФЭП',
            'head_name' => 'Петров П.П.',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $faculty3Id = DB::table('faculties')->insertGetId([
            'name' => 'Факультет сервиса и технологий',
            'short_name' => 'ФСТ',
            'head_name' => 'Сидоров С.С.',
            'is_active' => true,
            'sort_order' => 3,
        ]);

        $dept1Id = DB::table('departments')->insertGetId([
            'faculty_id' => $faculty1Id,
            'name' => 'Кафедра программирования и баз данных',
            'short_name' => 'ПиБД',
            'head_name' => 'Смирнов А.А.',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $dept2Id = DB::table('departments')->insertGetId([
            'faculty_id' => $faculty1Id,
            'name' => 'Кафедра компьютерных сетей',
            'short_name' => 'КС',
            'head_name' => 'Козлов В.В.',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $dept3Id = DB::table('departments')->insertGetId([
            'faculty_id' => $faculty2Id,
            'name' => 'Кафедра экономики и бухгалтерского учёта',
            'short_name' => 'ЭиБУ',
            'head_name' => 'Новикова Е.Е.',
            'is_active' => true,
            'sort_order' => 3,
        ]);

        $dept4Id = DB::table('departments')->insertGetId([
            'faculty_id' => $faculty2Id,
            'name' => 'Кафедра права и юриспруденции',
            'short_name' => 'ПиЮ',
            'head_name' => 'Морозов Д.Д.',
            'is_active' => true,
            'sort_order' => 4,
        ]);

        $dept5Id = DB::table('departments')->insertGetId([
            'faculty_id' => $faculty3Id,
            'name' => 'Кафедра дизайна и конструирования',
            'short_name' => 'ДиК',
            'head_name' => 'Белова О.О.',
            'is_active' => true,
            'sort_order' => 5,
        ]);

        $dept6Id = DB::table('departments')->insertGetId([
            'faculty_id' => $faculty3Id,
            'name' => 'Кафедра технологии продукции и организации питания',
            'short_name' => 'ТПиОП',
            'head_name' => 'Васильев К.К.',
            'is_active' => true,
            'sort_order' => 6,
        ]);

        $educationLevelSpO = DB::table('education_levels')->where('name', 'like', 'СПО%')->first();
        $educationLevelId = $educationLevelSpO ? $educationLevelSpO->id : 1;

        $specialties = [
            ['department_id' => $dept1Id, 'code' => '09.02.07', 'name' => 'Информационные системы и программирование', 'short_name' => 'ИСП', 'qualification' => 'Программист', 'education_level_id' => $educationLevelId, 'study_years' => 4, 'max_courses' => 4],
            ['department_id' => $dept2Id, 'code' => '09.02.06', 'name' => 'Сетевое и системное администрирование', 'short_name' => 'ССА', 'qualification' => 'Сетевой администратор', 'education_level_id' => $educationLevelId, 'study_years' => 4, 'max_courses' => 4],
            ['department_id' => $dept3Id, 'code' => '38.02.01', 'name' => 'Экономика и бухгалтерский учёт', 'short_name' => 'ЭиБУ', 'qualification' => 'Бухгалтер', 'education_level_id' => $educationLevelId, 'study_years' => 3, 'max_courses' => 3],
            ['department_id' => $dept4Id, 'code' => '40.02.01', 'name' => 'Право и организация социального обеспечения', 'short_name' => 'ПиОСО', 'qualification' => 'Юрист', 'education_level_id' => $educationLevelId, 'study_years' => 3, 'max_courses' => 3],
            ['department_id' => $dept5Id, 'code' => '54.02.01', 'name' => 'Дизайн (по отраслям)', 'short_name' => 'Дизайн', 'qualification' => 'Дизайнер', 'education_level_id' => $educationLevelId, 'study_years' => 4, 'max_courses' => 4],
        ];

        foreach ($specialties as $specialty) {
            DB::table('specialties')->insert($specialty);
        }

        $academicYear = DB::table('academic_years')->where('is_current', true)->first();
        $academicYearId = $academicYear ? $academicYear->id : 2;

        $allSpecialties = DB::table('specialties')->get();
        $groupsData = [];
        $courseNames = ['ИСП', 'ССА', 'ЭиБУ', 'ПиОСО', 'ДЗ'];

        $groupIndex = 0;
        foreach ($allSpecialties as $spec) {
            for ($course = 1; $course <= 3; $course++) {
                $specShort = $spec->short_name ?? $courseNames[$groupIndex % count($courseNames)];
                $groupName = $specShort.'-'.(21 + $course - 1);
                $shift = ($course <= 2) ? 1 : 2;
                $enrollmentYear = 2025 - ($course - 1);
                $groupsData[] = [
                    'specialty_id' => $spec->id,
                    'department_id' => $spec->department_id,
                    'academic_year_id' => $academicYearId,
                    'name' => $groupName,
                    'short_name' => $groupName,
                    'current_course' => $course,
                    'students_count' => 20 + rand(5, 15),
                    'shift' => $shift,
                    'status' => 'active',
                    'enrollment_date' => $enrollmentYear.'-09-01',
                    'graduation_date' => ($enrollmentYear + $spec->study_years).'-06-30',
                    'is_active' => true,
                ];
                $groupIndex++;
                if (count($groupsData) >= 15) {
                    break 2;
                }
            }
        }

        foreach ($groupsData as $group) {
            DB::table('groups')->insert($group);
        }

        $positions = DB::table('teacher_positions')->pluck('id')->toArray();
        $lastNames = ['Иванов', 'Петров', 'Сидоров', 'Смирнов', 'Кузнецов', 'Попов', 'Васильев', 'Зайцев', 'Павлов', 'Семёнов',
            'Голубев', 'Виноградов', 'Богданов', 'Воробьёв', 'Фёдоров', 'Михайлов', 'Белов', 'Тарасов', 'Комаров', 'Орлов',
            'Кириллов', 'Макаров', 'Андреев', 'Савельев', 'Алексеев'];
        $firstNames = ['Александр', 'Дмитрий', 'Максим', 'Сергей', 'Андрей', 'Алексей', 'Артём', 'Илья', 'Кирилл', 'Владимир',
            'Елена', 'Ольга', 'Наталья', 'Мария', 'Анна', 'Татьяна', 'Ирина', 'Светлана', 'Юлия', 'Екатерина',
            'Николай', 'Павел', 'Роман', 'Виктор', 'Валентин'];

        $allDepartments = [$dept1Id, $dept2Id, $dept3Id, $dept4Id, $dept5Id, $dept6Id];
        $teachersData = [];

        for ($i = 0; $i < 25; $i++) {
            $deptId = $allDepartments[$i % count($allDepartments)];
            $lastName = $lastNames[$i];
            $firstName = $firstNames[$i];
            $middleName = $firstNames[($i + 5) % 25].'ович';

            if (in_array($firstName, ['Елена', 'Ольга', 'Наталья', 'Мария', 'Анна', 'Татьяна', 'Ирина', 'Светлана', 'Юлия', 'Екатерина'])) {
                $middleName = $firstNames[($i + 5) % 25].'овна';
            }

            $fullName = $lastName.' '.$firstName.' '.$middleName;
            $positionId = $positions[array_rand($positions)];

            $teachersData[] = [
                'department_id' => $deptId,
                'position_id' => $positionId,
                'last_name' => $lastName,
                'first_name' => $firstName,
                'middle_name' => $middleName,
                'full_name' => $fullName,
                'short_name' => $lastName.' '.mb_substr($firstName, 0, 1).'.'.mb_substr($middleName, 0, 1).'.',
                'employment_type' => 'full_time',
                'rate' => 1,
                'max_hours_per_week' => 36,
                'min_lessons_per_day' => 3,
                'max_lessons_per_day' => 5,
                'is_active' => true,
            ];
        }

        foreach ($teachersData as $teacher) {
            DB::table('teachers')->insert($teacher);
        }

        $allTeachers = DB::table('teachers')->get();
        $allGroups = DB::table('groups')->get();

        $curriculumPlanId = DB::table('curriculum_plans')->insertGetId([
            'specialty_id' => $allSpecialties->first()->id,
            'academic_year_id' => $academicYearId,
            'name' => 'Учебный план 09.02.07 Информационные системы и программирование',
            'version' => 'v1.0',
            'total_hours' => 4428,
            'contact_hours' => 2952,
            'self_study_hours' => 1476,
            'practice_hours' => 0,
            'is_active' => true,
        ]);

        $controlForms = DB::table('control_forms')->pluck('id', 'name')->toArray();

        $disciplines = [
            ['name' => 'Основы программирования', 'short_name' => 'ОП', 'code' => 'ОП.01', 'cycle' => 'ОП', 'is_federal' => true, 'sort_order' => 1],
            ['name' => 'Базы данных', 'short_name' => 'БД', 'code' => 'ОП.02', 'cycle' => 'ОП', 'is_federal' => true, 'sort_order' => 2],
            ['name' => 'Компьютерные сети', 'short_name' => 'КС', 'code' => 'ОП.03', 'cycle' => 'ОП', 'is_federal' => true, 'sort_order' => 3],
            ['name' => 'Информационная безопасность', 'short_name' => 'ИБ', 'code' => 'ОП.04', 'cycle' => 'ОП', 'is_federal' => true, 'sort_order' => 4],
            ['name' => 'Веб-технологии', 'short_name' => 'ВТ', 'code' => 'ОП.05', 'cycle' => 'ОП', 'is_federal' => false, 'sort_order' => 5],
            ['name' => 'Иностранный язык', 'short_name' => 'ИЯ', 'code' => 'ОГСЭ.01', 'cycle' => 'ОГСЭ', 'is_federal' => true, 'sort_order' => 6],
            ['name' => 'Физическая культура', 'short_name' => 'ФК', 'code' => 'ОГСЭ.02', 'cycle' => 'ОГСЭ', 'is_federal' => true, 'sort_order' => 7],
            ['name' => 'Математика', 'short_name' => 'Мат', 'code' => 'ЕН.01', 'cycle' => 'ЕН', 'is_federal' => true, 'sort_order' => 8],
        ];

        foreach ($disciplines as $discipline) {
            $disciplineId = DB::table('curriculum_disciplines')->insertGetId([
                'curriculum_plan_id' => $curriculumPlanId,
                'name' => $discipline['name'],
                'short_name' => $discipline['short_name'],
                'code' => $discipline['code'],
                'cycle' => $discipline['cycle'],
                'discipline_type' => 'theoretical',
                'is_federal' => $discipline['is_federal'],
                'sort_order' => $discipline['sort_order'],
            ]);

            for ($course = 1; $course <= 2; $course++) {
                for ($semInCourse = 1; $semInCourse <= 2; $semInCourse++) {
                    $semesterNumber = ($course - 1) * 2 + $semInCourse;
                    $hoursTotal = rand(30, 80);
                    $hoursLecture = intval($hoursTotal * 0.4);
                    $hoursPractice = intval($hoursTotal * 0.4);
                    $hoursLab = $hoursTotal - $hoursLecture - $hoursPractice;
                    $hoursSelfStudy = intval($hoursTotal * 0.3);

                    DB::table('curriculum_semesters')->insert([
                        'discipline_id' => $disciplineId,
                        'course_number' => $course,
                        'semester_number' => $semesterNumber,
                        'semester_in_course' => $semInCourse,
                        'hours_total' => $hoursTotal,
                        'hours_lecture' => $hoursLecture,
                        'hours_practice' => $hoursPractice,
                        'hours_lab' => max(0, $hoursLab),
                        'hours_self_study' => $hoursSelfStudy,
                        'hours_consultation' => 2,
                        'control_form_id' => ($semInCourse == 2) ? ($controlForms['Экзамен'] ?? null) : ($controlForms['Зачёт'] ?? null),
                        'exam_hours' => ($semInCourse == 2) ? 6 : 0,
                        'weeks_count' => 16,
                        'hours_per_week' => round($hoursTotal / 16, 1),
                    ]);
                }
            }
        }

        $allDisciplines = DB::table('curriculum_disciplines')->where('curriculum_plan_id', $curriculumPlanId)->get();
        foreach ($allTeachers as $index => $teacher) {
            if ($index >= count($allDisciplines) * 2) {
                break;
            }
            $discipline = $allDisciplines[$index % count($allDisciplines)];
            $group = $allGroups[$index % count($allGroups)];
            DB::table('teacher_disciplines')->insert([
                'teacher_id' => $teacher->id,
                'discipline_id' => $discipline->id,
                'group_id' => $group->id,
                'academic_year_id' => $academicYearId,
                'is_primary' => true,
                'planned_hours' => 144,
                'actual_hours' => 0,
            ]);
        }

        if ($allGroups->isNotEmpty()) {
            DB::table('group_curriculum_assignments')->insert([
                'group_id' => $allGroups->first()->id,
                'curriculum_plan_id' => $curriculumPlanId,
                'assigned_at' => now(),
                'is_active' => true,
            ]);

            DB::table('group_buildings')->insert([
                'group_id' => $allGroups->first()->id,
                'building_id' => $building1Id,
                'is_primary' => true,
            ]);
        }
    }
}
