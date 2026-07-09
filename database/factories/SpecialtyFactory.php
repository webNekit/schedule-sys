<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Department;
use App\Models\Specialty;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Specialty>
 */
class SpecialtyFactory extends Factory
{
    protected $model = Specialty::class;

    public function definition(): array
    {
        return [
            'department_id' => Department::factory(),
            'code' => fake()->unique()->numerify('##.##.##'),
            'name' => 'Специальность '.fake()->words(2, true),
            'short_name' => mb_strtoupper(fake()->lexify('????')),
            'qualification' => fake()->jobTitle(),
            'study_years' => fake()->numberBetween(2, 4),
            'study_months' => fake()->randomElement([0, 6, 10]),
            'base_education' => fake()->randomElement(['9', '11']),
            'form_of_study' => 'очная',
            'max_courses' => 4,
            'is_active' => true,
        ];
    }
}
