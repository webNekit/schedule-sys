<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EducationLevel extends Model
{
    protected $fillable = [
        'name',
        'study_years',
    ];

    protected function casts(): array
    {
        return [
            'study_years' => 'integer',
        ];
    }

    public function specialties(): HasMany
    {
        return $this->hasMany(Specialty::class);
    }
}
