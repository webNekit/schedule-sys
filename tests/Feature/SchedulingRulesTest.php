<?php

declare(strict_types=1);

use App\Http\Livewire\Admin\SchedulingRules;
use App\Models\CurriculumDiscipline;
use App\Models\Group;
use App\Models\SchedulingRule;
use App\Services\Schedule\SchedulingRuleResolver;
use Database\Seeders\SchedulingRulesSeeder;
use Livewire\Livewire;

/** In-memory группа без обращения к БД — resolver читает только id и current_course. */
function fakeGroup(int $id, int $course): Group
{
    $group = new Group(['current_course' => $course]);
    $group->id = $id;
    $group->current_course = $course;

    return $group;
}

it('returns built-in default when no rules exist', function () {
    $resolver = new SchedulingRuleResolver;
    $resolver->load();

    expect($resolver->intParam('pairs_per_day', 'base', 0))->toBe(3)
        ->and($resolver->isEnabled('group_no_windows'))->toBeTrue()
        ->and($resolver->severity('group_no_windows'))->toBe('hard');
});

it('lets a global row override the built-in default', function () {
    SchedulingRule::create([
        'key' => 'pairs_per_day', 'scope' => 'global', 'scope_id' => null,
        'is_enabled' => true, 'severity' => null, 'params' => ['base' => 4],
    ]);

    $resolver = new SchedulingRuleResolver;
    $resolver->load();

    // base переопределён, max берётся из дефолта (частичный merge)
    expect($resolver->intParam('pairs_per_day', 'base', 0))->toBe(4)
        ->and($resolver->intParam('pairs_per_day', 'max', 0))->toBe(5);
});

it('resolves by priority group > course > global', function () {
    SchedulingRule::create(['key' => 'pairs_per_day', 'scope' => 'global', 'scope_id' => null, 'is_enabled' => true, 'params' => ['base' => 3]]);
    SchedulingRule::create(['key' => 'pairs_per_day', 'scope' => 'course', 'scope_id' => 2, 'is_enabled' => true, 'params' => ['base' => 4]]);
    SchedulingRule::create(['key' => 'pairs_per_day', 'scope' => 'group', 'scope_id' => 77, 'is_enabled' => true, 'params' => ['base' => 6]]);

    $resolver = new SchedulingRuleResolver;
    $resolver->load();

    // Группа курса 2 без своего override → значение курса
    expect($resolver->intParam('pairs_per_day', 'base', 0, fakeGroup(50, 2)))->toBe(4)
        // Группа 77 курса 2 → свой override побеждает курс
        ->and($resolver->intParam('pairs_per_day', 'base', 0, fakeGroup(77, 2)))->toBe(6)
        // Группа курса 1 → глобальный (нет course=1 / своего)
        ->and($resolver->intParam('pairs_per_day', 'base', 0, fakeGroup(9, 1)))->toBe(3);
});

it('resolves enabled flag and severity from the most specific row', function () {
    SchedulingRule::create(['key' => 'group_no_windows', 'scope' => 'global', 'scope_id' => null, 'is_enabled' => true, 'severity' => 'hard']);
    SchedulingRule::create(['key' => 'group_no_windows', 'scope' => 'group', 'scope_id' => 12, 'is_enabled' => false, 'severity' => 'soft']);

    $resolver = new SchedulingRuleResolver;
    $resolver->load();

    expect($resolver->isEnabled('group_no_windows', fakeGroup(12, 1)))->toBeFalse()
        ->and($resolver->severity('group_no_windows', fakeGroup(12, 1)))->toBe('soft')
        ->and($resolver->isEnabled('group_no_windows', fakeGroup(99, 1)))->toBeTrue();
});

it('exposes category helpers on disciplines', function () {
    expect((new CurriculumDiscipline(['category' => 'pe']))->isPhysicalEducation())->toBeTrue()
        ->and((new CurriculumDiscipline(['category' => 'practice']))->isPracticeCategory())->toBeTrue()
        ->and((new CurriculumDiscipline(['category' => 'exam']))->isExamCategory())->toBeTrue()
        ->and((new CurriculumDiscipline(['category' => 'general']))->isPhysicalEducation())->toBeFalse();
});

it('seeds one global row per default rule', function () {
    $this->seed(SchedulingRulesSeeder::class);

    expect(SchedulingRule::where('scope', 'global')->count())
        ->toBe(count(SchedulingRuleResolver::DEFAULTS));
});

it('saves a rule override through the management component', function () {
    Livewire::test(SchedulingRules::class)
        ->set('rows.pairs_per_day.params.base', 5)
        ->call('saveRule', 'pairs_per_day');

    $row = SchedulingRule::where('key', 'pairs_per_day')->where('scope', 'global')->first();
    expect($row)->not->toBeNull()
        ->and($row->params['base'])->toBe(5);
});

it('saves weekday checkboxes (array) as a clean int list', function () {
    Livewire::test(SchedulingRules::class)
        ->set('rows.min_lessons_check_weekdays.params.days', ['6', '7', '6'])
        ->call('saveRule', 'min_lessons_check_weekdays');

    $row = SchedulingRule::where('key', 'min_lessons_check_weekdays')->where('scope', 'global')->first();
    expect($row->params['days'])->toBe([6, 7]);
});

it('parses comma-separated array params and clears array values', function () {
    Livewire::test(SchedulingRules::class)
        ->set('rows.default_slots.params.shift1', '1, 2, 3')
        ->call('saveRule', 'default_slots');

    $row = SchedulingRule::where('key', 'default_slots')->where('scope', 'global')->first();
    expect($row->params['shift1'])->toBe([1, 2, 3]);
});
