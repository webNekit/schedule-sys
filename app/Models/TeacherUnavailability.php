<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherUnavailability extends Model
{
    protected $fillable = [
        'teacher_id',
        'type',
        'date_from',
        'date_to',
        'reason',
        'all_day',
        'time_from',
        'time_to',
        'is_approved',
        'approved_by',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'all_day' => 'boolean',
            'is_approved' => 'boolean',
            'date_from' => 'date',
            'date_to' => 'date',
            'time_from' => 'datetime:H:i',
            'time_to' => 'datetime:H:i',
        ];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
