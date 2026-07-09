<?php

declare(strict_types=1);

use App\Http\Livewire\Admin\CustomRules;
use App\Models\CustomSchedulingRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('builds a forbid rule from the visual builder', function () {
    Livewire::test(CustomRules::class)
        ->call('newRule')
        ->set('name', 'Нет субботы')
        ->set('severity', 'hard')
        ->set('matchConditions', [['field' => 'weekday', 'op' => 'in', 'value' => ['6']]])
        ->call('save')
        ->assertHasNoErrors();

    $rule = CustomSchedulingRule::first();
    expect($rule->name)->toBe('Нет субботы')
        ->and($rule->definition['type'])->toBe('forbid')
        ->and($rule->definition['match'])->toBe(['field' => 'weekday', 'op' => 'in', 'value' => [6]]);
});

it('combines several match conditions with the chosen combinator', function () {
    Livewire::test(CustomRules::class)
        ->call('newRule')
        ->set('name', 'Сложное')
        ->set('matchCombinator', 'any')
        ->set('matchConditions', [
            ['field' => 'weekday', 'op' => 'in', 'value' => ['6']],
            ['field' => 'lesson_number', 'op' => '>', 'value' => '4'],
        ])
        ->call('save')
        ->assertHasNoErrors();

    expect(CustomSchedulingRule::first()->definition['match'])->toBe([
        'any' => [
            ['field' => 'weekday', 'op' => 'in', 'value' => [6]],
            ['field' => 'lesson_number', 'op' => '>', 'value' => 4],
        ],
    ]);
});

it('builds a require (binding) rule', function () {
    Livewire::test(CustomRules::class)
        ->call('newRule')
        ->call('setType', 'require')
        ->set('name', 'Дисц.10 → корпус 2')
        ->set('matchConditions', [['field' => 'discipline_id', 'op' => '=', 'value' => '10']])
        ->set('requireConditions', [['field' => 'building_id', 'op' => '=', 'value' => '2']])
        ->call('save')
        ->assertHasNoErrors();

    $def = CustomSchedulingRule::first()->definition;
    expect($def['type'])->toBe('require')
        ->and($def['match'])->toBe(['field' => 'discipline_id', 'op' => '=', 'value' => 10])
        ->and($def['require'])->toBe(['field' => 'building_id', 'op' => '=', 'value' => 2]);
});

it('builds a limit rule', function () {
    Livewire::test(CustomRules::class)
        ->call('newRule')
        ->call('setType', 'limit')
        ->set('name', 'Препод ≤4/день')
        ->set('matchConditions', [['field' => 'teacher_id', 'op' => '=', 'value' => '7']])
        ->set('limitGroupBy', ['teacher_id', 'date'])
        ->set('limitOp', '<=')
        ->set('limitValue', 4)
        ->call('save')
        ->assertHasNoErrors();

    $def = CustomSchedulingRule::first()->definition;
    expect($def['type'])->toBe('limit')
        ->and($def['group_by'])->toBe(['teacher_id', 'date'])
        ->and($def['op'])->toBe('<=')
        ->and($def['value'])->toBe(4);
});

it('rejects a forbid rule with no conditions', function () {
    Livewire::test(CustomRules::class)
        ->call('newRule')
        ->set('name', 'Пусто')
        ->set('matchConditions', [])
        ->call('save')
        ->assertHasErrors('definition');

    expect(CustomSchedulingRule::count())->toBe(0);
});

it('still supports raw JSON in advanced mode', function () {
    Livewire::test(CustomRules::class)
        ->call('newRule')
        ->set('name', 'JSON-правило')
        ->set('advanced', true)
        ->set('definitionJson', json_encode([
            'type' => 'forbid',
            'match' => ['not' => ['field' => 'building_id', 'op' => '=', 'value' => 1]],
        ]))
        ->call('save')
        ->assertHasNoErrors();

    expect(CustomSchedulingRule::first()->definition['match'])->toBe(['not' => ['field' => 'building_id', 'op' => '=', 'value' => 1]]);
});

it('loads a nested rule into advanced mode on edit', function () {
    $rule = CustomSchedulingRule::create([
        'name' => 'Вложенное', 'scope' => 'global', 'severity' => 'soft', 'is_enabled' => true,
        'definition' => ['type' => 'forbid', 'match' => ['not' => ['field' => 'weekday', 'op' => 'in', 'value' => [6]]]],
    ]);

    Livewire::test(CustomRules::class)
        ->call('edit', $rule->id)
        ->assertSet('advanced', true);
});

it('toggles and deletes a rule', function () {
    $rule = CustomSchedulingRule::create([
        'name' => 'R', 'scope' => 'global', 'severity' => 'soft', 'is_enabled' => true,
        'definition' => ['type' => 'forbid', 'match' => ['field' => 'group_id', 'op' => '=', 'value' => 1]],
    ]);

    Livewire::test(CustomRules::class)->call('toggle', $rule->id);
    expect($rule->fresh()->is_enabled)->toBeFalse();

    Livewire::test(CustomRules::class)->call('delete', $rule->id);
    expect(CustomSchedulingRule::count())->toBe(0);
});
