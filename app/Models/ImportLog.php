<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportLog extends Model
{
    protected $fillable = [
        'user_id',
        'file_name',
        'file_path',
        'file_size',
        'type',
        'status',
        'records_total',
        'records_imported',
        'records_failed',
        'errors',
        'warnings',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'records_total' => 'integer',
            'records_imported' => 'integer',
            'records_failed' => 'integer',
            'errors' => 'array',
            'warnings' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
