<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ForeignLanguageGroup extends Model
{
    protected $fillable = [
        'name',
        'short_name',
    ];

    public function subgroups(): HasMany
    {
        return $this->hasMany(Subgroup::class);
    }
}
