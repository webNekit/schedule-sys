<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CurriculumDiscipline extends Model
{
    protected $fillable = [
        'curriculum_plan_id',
        'name',
        'short_name',
        'code',
        'cycle',
        'discipline_type',
        'is_federal',
        'requires_subgroup',
        'subgroup_type',
        'requires_lab',
        'required_room_type_id',
        'sort_order',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_federal' => 'boolean',
            'requires_subgroup' => 'boolean',
            'requires_lab' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function curriculumPlan(): BelongsTo
    {
        return $this->belongsTo(CurriculumPlan::class);
    }

    public function requiredRoomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class, 'required_room_type_id');
    }

    public function semesters(): HasMany
    {
        return $this->hasMany(CurriculumSemester::class, 'discipline_id');
    }

    public function teacherDisciplines(): HasMany
    {
        return $this->hasMany(TeacherDiscipline::class, 'discipline_id');
    }

    public function scheduleLessons(): HasMany
    {
        return $this->hasMany(ScheduleLesson::class, 'discipline_id');
    }
}
