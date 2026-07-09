<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Group;
use Illuminate\Support\Collection;

class GroupPromotionService
{
    public function promoteAllGroups(): array
    {
        $promoted = 0;
        $graduated = 0;

        // 1. Находим следующий учебный год
        $currentYear = AcademicYear::where('is_current', true)->first();
        $nextYear = null;

        if ($currentYear) {
            $nextYear = AcademicYear::where('year_start', '>', $currentYear->year_start)
                ->orderBy('year_start', 'asc')
                ->first();
        } else {
            $nextYear = AcademicYear::orderBy('year_start', 'asc')->first();
        }

        // 2. Получаем все активные группы, которые еще не выпустились
        $groups = Group::with('specialty')
            ->where('is_active', true)
            ->where('status', '!=', 'graduated')
            ->get();

        foreach ($groups as $group) {
            $maxCourses = $group->specialty?->max_courses ?? 4;

            if ($group->current_course < $maxCourses) {
                // Переводим на следующий курс
                $group->promote();
                $promoted++;
            } else {
                // Выпускаем — graduate() сам уводит курс за максимум.
                $group->graduate();
                $graduated++;
            }

            // Если есть следующий год, привязываем группу к нему
            if ($nextYear) {
                $group->update(['academic_year_id' => $nextYear->id]);
            }
        }

        // 3. Переключаем текущий год в системе
        if ($nextYear) {
            AcademicYear::where('is_current', true)->update(['is_current' => false]);
            $nextYear->update(['is_current' => true]);
        }

        return [
            'promoted' => $promoted,
            'graduated' => $graduated,
        ];
    }

    public function promoteGroup(Group $group): void
    {
        $group->promote();
    }

    public function graduateGroup(Group $group): void
    {
        $group->graduate();
    }

    public function getGroupsForPromotion(): Collection
    {
        return Group::with('specialty')
            ->where('is_active', true)
            ->where('status', '!=', 'graduated')
            ->get()
            ->filter(function (Group $group): bool {
                $maxCourses = $group->specialty?->max_courses ?? 4;

                return $group->current_course < $maxCourses;
            })->values();
    }

    public function getGroupsForGraduation(): Collection
    {
        return Group::with('specialty')
            ->where('is_active', true)
            ->where('status', '!=', 'graduated')
            ->get()
            ->filter(function (Group $group): bool {
                $maxCourses = $group->specialty?->max_courses ?? 4;

                return $group->current_course >= $maxCourses;
            })->values();
    }
}
