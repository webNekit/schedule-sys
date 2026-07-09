<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SportComplexSlot extends Model
{
    protected $table = 'sport_complex_schedule';

    protected $fillable = [
        'weekday',
        'lesson_number',
        'group_id',
    ];

    protected function casts(): array
    {
        return [
            'weekday' => 'integer',
            'lesson_number' => 'integer',
            'group_id' => 'integer',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }
}
