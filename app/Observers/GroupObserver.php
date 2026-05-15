<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Models\Group;

class GroupObserver
{
    public function created(Group $group): void
    {
        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'create',
            'module' => 'groups',
            'model_type' => Group::class,
            'model_id' => $group->id,
            'description' => "Создана группа: {$group->name}",
            'new_values' => $group->toArray(),
        ]);
    }

    public function updated(Group $group): void
    {
        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'update',
            'module' => 'groups',
            'model_type' => Group::class,
            'model_id' => $group->id,
            'description' => "Обновлена группа: {$group->name}",
            'old_values' => $group->getOriginal(),
            'new_values' => $group->toArray(),
        ]);
    }

    public function deleted(Group $group): void
    {
        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'delete',
            'module' => 'groups',
            'model_type' => Group::class,
            'model_id' => $group->id,
            'description' => "Удалена группа: {$group->name}",
            'old_values' => $group->toArray(),
        ]);
    }

    public function restored(Group $group): void
    {
        //
    }

    public function forceDeleted(Group $group): void
    {
        //
    }
}
