<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupDayBuilding extends Model
{
    protected $fillable = [
        'group_id',
        'date',
        'building_id',
        'auto_determined',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'auto_determined' => 'boolean',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }
}
