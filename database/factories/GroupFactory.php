<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\Department;
use App\Models\Group;
use App\Models\Specialty;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Group>
 */
class GroupFactory extends Factory
{
    protected $model = Group::class;

    public function definition(): array
    {
        $specialty = Specialty::factory();

        return [
            'specialty_id' => $specialty,
            // Отделение группы наследуется от отделения специальности,
            // чтобы данные оставались согласованными.
            'department_id' => fn (array $attributes) => Specialty::find($attributes['specialty_id'])?->department_id
                ?? Department::factory(),
            'academic_year_id' => AcademicYear::factory(),
            'name' => mb_strtoupper(fake()->unique()->bothify('??-##')),
            'short_name' => null,
            'current_course' => fake()->numberBetween(1, 4),
            'students_count' => fake()->numberBetween(15, 30),
            'shift' => 1,
            'status' => 'active',
            'enrollment_year' => fake()->numberBetween(2020, 2025),
            'is_active' => true,
        ];
    }

    /**
     * Выпущенная группа.
     */
    public function graduated(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'graduated',
        ]);
    }
}
