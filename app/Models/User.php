<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'teacher_id', 'department_id', 'is_active', 'last_login_at', 'avatar'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles')
            ->withPivot('department_id')
            ->withTimestamps();
    }

    public function hasPermission(string $permissionSlug): bool
    {
        return $this->roles()
            ->whereHas('permissions', fn ($q) => $q->where('slug', $permissionSlug))
            ->exists();
    }

    public function hasRole(string $roleSlug): bool
    {
        return $this->roles()->where('slug', $roleSlug)->exists();
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function importLogs(): HasMany
    {
        return $this->hasMany(ImportLog::class);
    }

    public function exportLogs(): HasMany
    {
        return $this->hasMany(ExportLog::class);
    }

    public function createdCurriculumPlans(): HasMany
    {
        return $this->hasMany(CurriculumPlan::class, 'created_by');
    }

    public function createdSchedules(): HasMany
    {
        return $this->hasMany(ScheduleVersion::class, 'created_by');
    }

    public function publishedSchedules(): HasMany
    {
        return $this->hasMany(ScheduleVersion::class, 'published_by');
    }

    public function createdLessons(): HasMany
    {
        return $this->hasMany(ScheduleLesson::class, 'created_by');
    }

    public function createdRoomUnavailabilities(): HasMany
    {
        return $this->hasMany(RoomUnavailability::class, 'created_by');
    }

    public function assignedGroupCurriculums(): HasMany
    {
        return $this->hasMany(GroupCurriculumAssignment::class, 'assigned_by');
    }

    public function approvedUnavailabilities(): HasMany
    {
        return $this->hasMany(TeacherUnavailability::class, 'approved_by');
    }

    public function createdUnavailabilities(): HasMany
    {
        return $this->hasMany(TeacherUnavailability::class, 'created_by');
    }

    public function resolvedConflicts(): HasMany
    {
        return $this->hasMany(ScheduleConflict::class, 'resolved_by');
    }
}
