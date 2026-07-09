<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\Schedule\CustomRuleEvaluator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Авторское (пользовательское) правило расписания.
 *
 * В отличие от {@see SchedulingRule} (настройка встроенных правил по ключу),
 * это правило целиком описывается данными в поле `definition` и исполняется
 * универсальным движком {@see CustomRuleEvaluator}.
 *
 * Формат `definition`:
 *  - type: forbid | require | limit
 *  - match:  <condition>  — на какие пары распространяется правило
 *  - require: <condition> — (для require) чему пара обязана удовлетворять
 *  - group_by/metric/op/value — (для limit) агрегатное ограничение
 *
 * <condition> рекурсивна: {all:[...]} | {any:[...]} | {not:{...}} | {field,op,value}.
 */
class CustomSchedulingRule extends Model
{
    protected $fillable = [
        'name',
        'description',
        'scope',
        'scope_id',
        'is_enabled',
        'severity',
        'definition',
    ];

    protected function casts(): array
    {
        return [
            'scope_id' => 'integer',
            'is_enabled' => 'boolean',
            'definition' => 'array',
        ];
    }

    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('is_enabled', true);
    }
}
