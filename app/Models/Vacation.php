<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vacation extends Model
{
    protected $fillable = [
        'academic_year_id',
        'name',
        'start_date',
        'end_date',
        'duration_days',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'duration_days' => 'integer',
        ];
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    protected static function booted(): void
    {
        static::saving(function (Vacation $vacation) {
            if ($vacation->start_date && $vacation->end_date) {
                $vacation->duration_days = Carbon::parse($vacation->start_date)
                    ->diffInDays(Carbon::parse($vacation->end_date));
            }
        });
    }

    public function includesDate(Carbon $date): bool
    {
        return $date->between(
            Carbon::parse($this->start_date),
            Carbon::parse($this->end_date),
        );
    }
}
