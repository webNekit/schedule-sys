<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Building;
use App\Models\CurriculumDiscipline;
use App\Models\CurriculumSemester;
use App\Models\Group;
use App\Models\Room;
use App\Models\TeacherDiscipline;
use App\Models\TeacherDisciplineSemester;
use Illuminate\Database\Seeder;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        $building = Building::firstOrCreate(['name' => 'Корпус Нестерова 1а'], ['short_name' => 'Корпус Нестерова 1а']);

        $rooms = [
            ['number' => '101', 'name' => 'Аудитория 101', 'capacity' => 30, 'floor' => 1, 'room_type_id' => 6],
            ['number' => '102', 'name' => 'Аудитория 102', 'capacity' => 25, 'floor' => 1, 'room_type_id' => 6],
            ['number' => '103', 'name' => 'Аудитория 103', 'capacity' => 20, 'floor' => 1, 'room_type_id' => 6],
            ['number' => '104', 'name' => 'Аудитория 104', 'capacity' => 30, 'floor' => 1, 'room_type_id' => 6],
            ['number' => '105', 'name' => 'Аудитория 105', 'capacity' => 15, 'floor' => 1, 'room_type_id' => 4],
            ['number' => '201', 'name' => 'Аудитория 201', 'capacity' => 35, 'floor' => 2, 'room_type_id' => 6],
            ['number' => '202', 'name' => 'Аудитория 202', 'capacity' => 25, 'floor' => 2, 'room_type_id' => 6],
            ['number' => '203', 'name' => 'Аудитория 203', 'capacity' => 20, 'floor' => 2, 'room_type_id' => 4],
            ['number' => '204', 'name' => 'Аудитория 204', 'capacity' => 30, 'floor' => 2, 'room_type_id' => 6],
            ['number' => '205', 'name' => 'Аудитория 205', 'capacity' => 20, 'floor' => 2, 'room_type_id' => 6],
            ['number' => '301', 'name' => 'Аудитория 301', 'capacity' => 40, 'floor' => 3, 'room_type_id' => 6],
            ['number' => '302', 'name' => 'Аудитория 302', 'capacity' => 25, 'floor' => 3, 'room_type_id' => 4],
            ['number' => '303', 'name' => 'Аудитория 303', 'capacity' => 30, 'floor' => 3, 'room_type_id' => 6],
            ['number' => '304', 'name' => 'Аудитория 304', 'capacity' => 15, 'floor' => 3, 'room_type_id' => 6],
            ['number' => '305', 'name' => 'Аудитория 305', 'capacity' => 20, 'floor' => 3, 'room_type_id' => 4],
            ['number' => '401', 'name' => 'Аудитория 401', 'capacity' => 30, 'floor' => 4, 'room_type_id' => 6],
            ['number' => '402', 'name' => 'Аудитория 402', 'capacity' => 25, 'floor' => 4, 'room_type_id' => 6],
            ['number' => '403', 'name' => 'Аудитория 403', 'capacity' => 20, 'floor' => 4, 'room_type_id' => 4],
            ['number' => '404', 'name' => 'Аудитория 404', 'capacity' => 35, 'floor' => 4, 'room_type_id' => 6],
            ['number' => '405', 'name' => 'Аудитория 405', 'capacity' => 15, 'floor' => 4, 'room_type_id' => 5],
        ];

        foreach ($rooms as $room) {
            Room::firstOrCreate(
                ['number' => $room['number'], 'building_id' => $building->id],
                $room
            );
        }

        foreach (Group::all() as $group) {
            $group->buildings()->syncWithoutDetaching([$building->id => ['is_primary' => true]]);
        }

        $disciplineAssignments = [
            'Русский язык' => 1,
            'Литература' => 2,
            'История' => 3,
            'Обществознание' => 4,
            'География' => 5,
            'Иностранный язык' => 6,
            'Информатика' => 7,
            'Физическая культура' => 8,
            'Основы безопасности и защиты Родины' => 9,
            'Химия' => 10,
            'Биология' => 11,
            'Математика' => 12,
            'Физика' => 1,
            'Введение в специальности' => 2,
            'Основы проектной деятельности' => 3,
            'Основы философии' => 4,
            'Иностранный язык в профессиональной' => 5,
            'Информационные технологии в профессиональной' => 6,
            'Экологические основы природопользования' => 7,
            'Технологии автоматизированного машиностроения' => 8,
            'Метрология стандартизация' => 9,
            'Технологическое оборудование' => 10,
            'Инженерная графика' => 11,
            'Материаловедение' => 12,
            'Программирование ЧПУ' => 1,
            'Экономика организации' => 2,
            'Охрана труда' => 3,
            'Техническая механика' => 4,
            'Процессы формообразования' => 5,
            'САПР технологических процессов' => 6,
            'Моделирование технологических процессов' => 7,
            'Основы электротехники' => 8,
            'Основы проектирования технологической оснастки' => 9,
            'Безопасность жизнедеятельности' => 10,
            'Электронная техника' => 11,
            'Электротехнические измерения' => 12,
            'Гидравлика, пневматика' => 1,
            'Разработка и компьютерное моделирование элементов систем автоматизации' => 2,
            'Осуществление анализа решений' => 3,
            'Тестирование разработанной модели' => 4,
            'Разработка и компьютерное моделирование элементов' => 5,
            'Экзамен по модулю Разработка' => 6,
            'Осуществление сборки и апробации' => 7,
            'Осуществление выбора оборудования' => 8,
            'Испытания модели элементов' => 9,
            'Экзамен по модулю Осуществление сборки' => 10,
            'Организация монтажа, наладки' => 11,
            'Планирование материально-технического' => 12,
            'Разработка, организация и контроль' => 1,
            'Экзамен по модулю Организация' => 2,
            'Осуществление текущего мониторинга' => 3,
            'Организация работ по устранению' => 4,
            'Выполнение работ по одной или нескольким профессиям' => 5,
            'Организация деятельности оператора' => 6,
            'Квалификационный экзамен Оператор' => 7,
            'ПРОИЗВОДСТВЕННАЯ ПРАКТИКА' => 8,
            'Подготовка выпускной квалификационной' => 9,
            'Защита выпускной квалификационной' => 10,
            'Подготовка к государственным экзаменам' => 11,
            'Проведение государственных экзаменов' => 12,
            'Общеобразовательные дисциплины' => 1,
        ];

        $plans = [1, 2];

        foreach ($plans as $planId) {
            $disciplines = CurriculumDiscipline::where('curriculum_plan_id', $planId)->get();
            $academicYearId = $planId === 1 ? 2 : 3;

            foreach ($disciplines as $discipline) {
                $existing = TeacherDiscipline::where('discipline_id', $discipline->id)
                    ->where('academic_year_id', $academicYearId)
                    ->first();

                if ($existing) {
                    continue;
                }

                $teacherId = null;
                foreach ($disciplineAssignments as $keyword => $tid) {
                    if (mb_stripos($discipline->name, $keyword) !== false) {
                        $teacherId = $tid;
                        break;
                    }
                }

                if (! $teacherId) {
                    continue;
                }

                $td = TeacherDiscipline::create([
                    'teacher_id' => $teacherId,
                    'discipline_id' => $discipline->id,
                    'group_id' => null,
                    'academic_year_id' => $academicYearId,
                    'is_primary' => true,
                    'planned_hours' => 0,
                ]);

                $semesters = CurriculumSemester::where('discipline_id', $discipline->id)->get();
                $totalHours = 0;

                foreach ($semesters as $sem) {
                    TeacherDisciplineSemester::firstOrCreate([
                        'teacher_discipline_id' => $td->id,
                        'curriculum_semester_id' => $sem->id,
                    ], [
                        'planned_hours' => $sem->hours_total,
                        'is_active' => true,
                    ]);
                    $totalHours += $sem->hours_total;
                }

                if ($totalHours > 0) {
                    $td->update(['planned_hours' => $totalHours]);
                }
            }
        }

        echo 'Rooms created: '.Room::count()."\n";
        echo 'TeacherDiscipline records: '.TeacherDiscipline::count()."\n";
        echo 'TeacherDisciplineSemester records: '.TeacherDisciplineSemester::count()."\n";
    }
}
