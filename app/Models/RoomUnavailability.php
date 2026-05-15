<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoomUnavailability extends Model
{
    protected $fillable = [
        'room_id',
        'type',
        'date_from',
        'date_to',
        'all_day',
        'time_from',
        'time_to',
        'reason',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'all_day' => 'boolean',
            'date_from' => 'date',
            'date_to' => 'date',
            'time_from' => 'datetime:H:i',
            'time_to' => 'datetime:H:i',
        ];
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
