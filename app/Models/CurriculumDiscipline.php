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
        'category',
        'is_federal',
        'requires_subgroup',
        'is_parallel',
        'subgroup_type',
        'requires_lab',
        'is_schedulable',
        'required_room_type_id',
        'sort_order',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_federal' => 'boolean',
            'requires_subgroup' => 'boolean',
            'is_parallel' => 'boolean',
            'requires_lab' => 'boolean',
            'is_schedulable' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function isPhysicalEducation(): bool
    {
        return $this->category === 'pe';
    }

    public function isPracticeCategory(): bool
    {
        return $this->category === 'practice';
    }

    public function isExamCategory(): bool
    {
        return $this->category === 'exam';
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

    /**
     * Экзамен профессионального модуля (ПМ): квалификационный, демонстрационный,
     * «экзамен по модулю». Определяется по коду (…ЭК) или названию — устойчиво
     * к разным форматам импорта (латинская/кириллическая M, is_schedulable 0/1).
     */
    public function isModuleExam(): bool
    {
        $code = mb_strtoupper((string) $this->code);
        // Код содержит ЭК в начале сегмента: "ПМ.01.ЭК", "ЭК.01" и т.п.
        if (preg_match('/(^|\.)ЭК/u', $code)) {
            return true;
        }

        return (bool) preg_match(
            '/экзамен\s+по\s+модул|квалификацион\w*\s+экзамен|демонстрацион\w*\s+экзамен/iu',
            (string) $this->name
        );
    }

    /**
     * Раздел-заголовок учебного плана (цикл ОД/ОГСЭ/ЕН/ОПЦ/ПЦ или контейнер модуля ПМ.xx,
     * ГИА) — это не дисциплина и не экзамен, его не нужно показывать в списках.
     */
    public function isSectionHeader(): bool
    {
        if ($this->is_schedulable || $this->isModuleExam()) {
            return false;
        }

        $code = mb_strtoupper(trim((string) $this->code));

        // Цикл без номера (ОД, ОГСЭ, ЕН, ОП, ФК…) или контейнер модуля/ГИА/практики
        return preg_match('/^(ОД|ОГСЭ|ЕН|ОП|ОПЦ|ПЦ|ПП|ФК|ФЦД)$/u', $code) === 1
            || preg_match('/^(ПМ|ГИА|УП|ПП|ПДП)\b/u', $code) === 1
            || ! $this->is_schedulable;
    }
}
