<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\CurriculumSemester;
use App\Models\TeacherDiscipline;
use App\Models\TeacherDisciplineSemester;
use Illuminate\Console\Command;

class BackfillTeacherDisciplineSemesters extends Command
{
    protected $signature = 'teachers:backfill-semesters';

    protected $description = 'Create semester hour records for existing teacher_disciplines';

    public function handle(): int
    {
        $tds = TeacherDiscipline::all();

        foreach ($tds as $td) {
            $existing = TeacherDisciplineSemester::where('teacher_discipline_id', $td->id)->count();
            if ($existing > 0) {
                continue;
            }

            $semesters = CurriculumSemester::where('discipline_id', $td->discipline_id)->get();
            foreach ($semesters as $semester) {
                TeacherDisciplineSemester::create([
                    'teacher_discipline_id' => $td->id,
                    'curriculum_semester_id' => $semester->id,
                    'planned_hours' => $semester->hours_total,
                ]);
            }

            $this->info("Backfilled {$semesters->count()} semesters for teacher_discipline #{$td->id}");
        }

        return self::SUCCESS;
    }
}
