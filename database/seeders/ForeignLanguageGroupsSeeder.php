<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ForeignLanguageGroupsSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        if (DB::table('foreign_language_groups')->exists()) {
            return;
        }

        DB::table('foreign_language_groups')->insert([
            ['name' => 'Английский язык', 'short_name' => 'EN'],
            ['name' => 'Немецкий язык', 'short_name' => 'DE'],
            ['name' => 'Французский язык', 'short_name' => 'FR'],
        ]);
    }
}
