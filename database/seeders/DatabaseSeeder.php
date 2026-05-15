<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            AdminUserSeeder::class,
            EducationLevelsSeeder::class,
            QualificationTypesSeeder::class,
            TeacherPositionsSeeder::class,
            LessonTypesSeeder::class,
            ControlFormsSeeder::class,
            RoomTypesSeeder::class,
            EquipmentTypesSeeder::class,
            WeekTypesSeeder::class,
            ForeignLanguageGroupsSeeder::class,
            BellScheduleSeeder::class,
            AcademicYearsSeeder::class,
            VacationsSeeder::class,
            HolidaysSeeder::class,
            SystemSettingsSeeder::class,
        ]);

        if (app()->environment('local')) {
            $this->call(DemoDataSeeder::class);
        }
    }
}
