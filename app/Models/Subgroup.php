<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subgroup extends Model
{
    protected $fillable = [
        'group_id',
        'name',
        'number',
        'type',
        'foreign_language_group_id',
        'students_count',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'number' => 'integer',
            'students_count' => 'integer',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function foreignLanguageGroup(): BelongsTo
    {
        return $this->belongsTo(ForeignLanguageGroup::class);
    }
}
