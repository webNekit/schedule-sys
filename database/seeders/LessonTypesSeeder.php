<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LessonTypesSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        if (DB::table('lesson_types')->exists()) {
            return;
        }

        $lessonTypes = [
            ['name' => 'Лекция', 'code' => 'lecture', 'color' => '#3B82F6', 'requires_lab' => false, 'is_control_form' => false],
            ['name' => 'Практическое занятие', 'code' => 'practice', 'color' => '#10B981', 'requires_lab' => false, 'is_control_form' => false],
            ['name' => 'Лабораторная работа', 'code' => 'lab', 'color' => '#F59E0B', 'requires_lab' => true, 'is_control_form' => false],
            ['name' => 'Самостоятельная работа', 'code' => 'self_study', 'color' => '#6B7280', 'requires_lab' => false, 'is_control_form' => false],
            ['name' => 'Учебная практика', 'code' => 'edu_practice', 'color' => '#8B5CF6', 'requires_lab' => false, 'is_control_form' => false],
            ['name' => 'Производственная практика', 'code' => 'prod_practice', 'color' => '#EC4899', 'requires_lab' => false, 'is_control_form' => false],
            ['name' => 'Консультация', 'code' => 'consultation', 'color' => '#14B8A6', 'requires_lab' => false, 'is_control_form' => false],
            ['name' => 'Зачёт', 'code' => 'test', 'color' => '#EF4444', 'requires_lab' => false, 'is_control_form' => true],
            ['name' => 'Экзамен', 'code' => 'exam', 'color' => '#DC2626', 'requires_lab' => false, 'is_control_form' => true],
            ['name' => 'Дифференцированный зачёт', 'code' => 'diff_test', 'color' => '#B91C1C', 'requires_lab' => false, 'is_control_form' => true],
            ['name' => 'Курсовой проект', 'code' => 'course_project', 'color' => '#7C3AED', 'requires_lab' => false, 'is_control_form' => false],
            ['name' => 'Курсовая работа', 'code' => 'course_work', 'color' => '#6D28D9', 'requires_lab' => false, 'is_control_form' => false],
            ['name' => 'Контрольная работа', 'code' => 'control_work', 'color' => '#D97706', 'requires_lab' => false, 'is_control_form' => false],
        ];

        DB::table('lesson_types')->insert($lessonTypes);
    }
}
