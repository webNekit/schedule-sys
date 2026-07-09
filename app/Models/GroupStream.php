<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GroupStream extends Model
{
    protected $fillable = [
        'name',
        'discipline_id',
        'semester_number',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'semester_number' => 'integer',
        ];
    }

    public function discipline(): BelongsTo
    {
        return $this->belongsTo(CurriculumDiscipline::class, 'discipline_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(GroupStreamMember::class, 'stream_id');
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'group_stream_members', 'stream_id', 'group_id')
            ->withTimestamps();
    }
}
