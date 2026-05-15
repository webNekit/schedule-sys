<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WeekTypesSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        if (DB::table('week_types')->exists()) {
            return;
        }

        DB::table('week_types')->insert([
            ['name' => 'Каждую неделю', 'code' => 'every'],
            ['name' => 'По числителю', 'code' => 'numerator'],
            ['name' => 'По знаменателю', 'code' => 'denominator'],
        ]);
    }
}
