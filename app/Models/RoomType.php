<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RoomType extends Model
{
    protected $fillable = [
        'name',
        'short_name',
        'color',
        'icon',
        'can_be_shared',
        'is_sport_complex',
    ];

    protected function casts(): array
    {
        return [
            'can_be_shared' => 'boolean',
            'is_sport_complex' => 'boolean',
        ];
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }
}
