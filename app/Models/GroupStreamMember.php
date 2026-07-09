<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupStreamMember extends Model
{
    protected $fillable = [
        'stream_id',
        'group_id',
    ];

    public function stream(): BelongsTo
    {
        return $this->belongsTo(GroupStream::class, 'stream_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }
}
