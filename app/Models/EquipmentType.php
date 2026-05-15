<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EquipmentType extends Model
{
    protected $fillable = [
        'name',
        'icon',
    ];
}
