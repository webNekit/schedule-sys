<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicYear extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'year_start',
        'year_end',
        'date_start',
        'date_end',
        'first_semester_start',
        'first_semester_end',
        'second_semester_start',
        'second_semester_end',
        'is_current',
    ];

    protected function casts(): array
    {
        return [
            'is_current' => 'boolean',
            'year_start' => 'integer',
            'year_end' => 'integer',
            'date_start' => 'date',
            'date_end' => 'date',
            'first_semester_start' => 'date',
            'first_semester_end' => 'date',
            'second_semester_start' => 'date',
            'second_semester_end' => 'date',
        ];
    }

    public function vacations(): HasMany
    {
        return $this->hasMany(Vacation::class);
    }

    public function scopeCurrent(Builder $query): Builder
    {
        return $query->where('is_current', true);
    }
}
