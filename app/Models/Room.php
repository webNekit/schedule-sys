<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Room extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'building_id',
        'room_type_id',
        'number',
        'name',
        'capacity',
        'area',
        'floor',
        'description',
        'is_active',
        'is_available_for_booking',
        'notes',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_available_for_booking' => 'boolean',
            'capacity' => 'integer',
            'floor' => 'integer',
            'area' => 'decimal:2',
        ];
    }

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }

    public function equipment(): BelongsToMany
    {
        return $this->belongsToMany(EquipmentType::class, 'room_equipment')
            ->withPivot('quantity', 'notes')
            ->withTimestamps();
    }

    public function roomEquipment(): HasMany
    {
        return $this->hasMany(RoomEquipment::class);
    }

    public function teacherRooms(): HasMany
    {
        return $this->hasMany(TeacherRoom::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
