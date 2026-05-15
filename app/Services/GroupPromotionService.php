<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Group;
use Illuminate\Support\Collection;

class GroupPromotionService
{
    public function promoteAllGroups(): array
    {
        $promoted = 0;
        $graduated = 0;

        foreach ($this->getGroupsForPromotion() as $group) {
            $this->promoteGroup($group);
            $promoted++;
        }

        foreach ($this->getGroupsForGraduation() as $group) {
            $this->graduateGroup($group);
            $graduated++;
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
        return Group::where('is_active', true)
            ->where('status', '!=', 'graduated')
            ->get()
            ->filter(function (Group $group): bool {
                $maxCourses = $group->specialty?->max_courses ?? 4;

                return $group->current_course < $maxCourses;
            })->values();
    }

    public function getGroupsForGraduation(): Collection
    {
        return Group::where('is_active', true)
            ->where('status', '!=', 'graduated')
            ->get()
            ->filter(function (Group $group): bool {
                $maxCourses = $group->specialty?->max_courses ?? 4;

                return $group->current_course >= $maxCourses;
            })->values();
    }
}
