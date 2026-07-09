<?php

declare(strict_types=1);

use App\Http\Livewire\Groups\Index;
use App\Models\AcademicYear;
use App\Models\Group;
use Livewire\Livewire;

/**
 * Smoke-тест страницы групп. Рендерится под включённым preventLazyLoading
 * (см. AppServiceProvider), поэтому любой N+1 в шаблоне уронит тест.
 */
it('renders the groups index without lazy-loading violations', function () {
    $year = AcademicYear::factory()->current()->create();
    Group::factory()->count(5)->create(['academic_year_id' => $year->id]);

    Livewire::test(Index::class)
        ->assertOk()
        ->assertViewHas('groups', fn ($groups) => $groups->total() === 5);
});

it('filters groups by course', function () {
    $year = AcademicYear::factory()->current()->create();
    Group::factory()->count(3)->create(['academic_year_id' => $year->id, 'current_course' => 1]);
    Group::factory()->count(2)->create(['academic_year_id' => $year->id, 'current_course' => 3]);

    Livewire::test(Index::class)
        ->set('courseFilter', '3')
        ->assertOk()
        ->assertViewHas('groups', fn ($groups) => $groups->total() === 2);
});
