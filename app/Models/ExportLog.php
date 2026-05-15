<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExportLog extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'file_name',
        'file_path',
        'file_size',
        'parameters',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'parameters' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
