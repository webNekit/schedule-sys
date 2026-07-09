<?php

use App\Models\AcademicYear;
use App\Models\Department;
use App\Models\Group;
use App\Models\Specialty;
use App\Services\GroupPromotionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('groups are promoted correctly to the next year', function () {
    // 1. Setup Academic Years
    $currentYear = AcademicYear::create([
        'name' => '2023/2024',
        'year_start' => 2023,
        'year_end' => 2024,
        'date_start' => '2023-09-01',
        'date_end' => '2024-08-31',
        'is_current' => true,
    ]);

    $nextYear = AcademicYear::create([
        'name' => '2024/2025',
        'year_start' => 2024,
        'year_end' => 2025,
        'date_start' => '2024-09-01',
        'date_end' => '2025-08-31',
        'is_current' => false,
    ]);

    $department = Department::create([
        'name' => 'Test Department',
        'short_name' => 'TD',
    ]);

    // 2. Setup Specialty
    $specialty = Specialty::create([
        'name' => 'Test Specialty',
        'short_name' => 'TS',
        'code' => '123',
        'department_id' => $department->id,
        'max_courses' => 4,
        'is_active' => true,
    ]);

    // 3. Setup Groups
    $group4 = Group::create([
        'name' => 'сп-1-22',
        'specialty_id' => $specialty->id,
        'department_id' => $department->id,
        'academic_year_id' => $currentYear->id,
        'current_course' => 4,
        'status' => 'active',
        'is_active' => true,
    ]);

    $group3 = Group::create([
        'name' => 'сп-1-23',
        'specialty_id' => $specialty->id,
        'department_id' => $department->id,
        'academic_year_id' => $currentYear->id,
        'current_course' => 3,
        'status' => 'active',
        'is_active' => true,
    ]);

    // 4. Run Promotion
    $service = new GroupPromotionService;
    $result = $service->promoteAllGroups();

    // 5. Assertions
    // Re-fetch groups
    $group4->refresh();
    $group3->refresh();

    // Check counts reported by service
    expect($result['promoted'])->toBe(1);
    expect($result['graduated'])->toBe(1);

    // Check сп-1-23 (was 3)
    expect($group3->current_course)->toBe(4);
    expect($group3->status)->toBe('active');
    expect($group3->academic_year_id)->toBe($nextYear->id);
    expect($group3->full_name)->toBe('сп-1-23 (4 курс)');

    // Check сп-1-22 (was 4)
    expect($group4->status)->toBe('graduated');
    expect($group4->academic_year_id)->toBe($nextYear->id);
    expect($group4->full_name)->toBe('сп-1-22 (выпущена)');

    // Check system academic year
    $currentYear->refresh();
    $nextYear->refresh();
    expect($currentYear->is_current)->toBeFalse();
    expect($nextYear->is_current)->toBeTrue();

    // 6. Test manual return of graduated group to course 3
    $group4->update(['current_course' => 3]);
    expect($group4->status)->toBe('active');
    expect($group4->full_name)->toBe('сп-1-22 (3 курс)');
});

test('single graduate() on a final-course group sticks', function () {
    $department = Department::create(['name' => 'Dept', 'short_name' => 'D']);
    $specialty = Specialty::create([
        'name' => 'Spec', 'short_name' => 'S', 'code' => '1',
        'department_id' => $department->id, 'max_courses' => 4, 'is_active' => true,
    ]);
    $year = AcademicYear::create([
        'name' => '2023/2024', 'year_start' => 2023, 'year_end' => 2024,
        'date_start' => '2023-09-01', 'date_end' => '2024-08-31', 'is_current' => true,
    ]);

    $group = Group::create([
        'name' => 'гр-4', 'specialty_id' => $specialty->id, 'department_id' => $department->id,
        'academic_year_id' => $year->id, 'current_course' => 4, 'status' => 'active', 'is_active' => true,
    ]);

    $group->graduate();
    $group->refresh();

    // Раньше хук saving сбрасывал статус обратно на active — теперь выпуск держится.
    expect($group->status)->toBe('graduated')
        ->and($group->current_course)->toBe(5)
        ->and($group->full_name)->toBe('гр-4 (выпущена)');
});

test('manually creating a graduated final-course group is not reactivated', function () {
    $department = Department::create(['name' => 'Dept', 'short_name' => 'D']);
    $specialty = Specialty::create([
        'name' => 'Spec', 'short_name' => 'S', 'code' => '1',
        'department_id' => $department->id, 'max_courses' => 4, 'is_active' => true,
    ]);
    $year = AcademicYear::create([
        'name' => '2023/2024', 'year_start' => 2023, 'year_end' => 2024,
        'date_start' => '2023-09-01', 'date_end' => '2024-08-31', 'is_current' => true,
    ]);

    $group = Group::create([
        'name' => 'гр-old', 'specialty_id' => $specialty->id, 'department_id' => $department->id,
        'academic_year_id' => $year->id, 'current_course' => 4, 'status' => 'graduated', 'is_active' => true,
    ]);

    expect($group->fresh()->status)->toBe('graduated');
});
