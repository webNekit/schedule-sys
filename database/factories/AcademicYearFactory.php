<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AcademicYear;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AcademicYear>
 */
class AcademicYearFactory extends Factory
{
    protected $model = AcademicYear::class;

    public function definition(): array
    {
        $yearStart = fake()->unique()->numberBetween(2020, 2035);
        $yearEnd = $yearStart + 1;

        return [
            'name' => "{$yearStart}-{$yearEnd}",
            'year_start' => $yearStart,
            'year_end' => $yearEnd,
            'date_start' => "{$yearStart}-09-01",
            'date_end' => "{$yearEnd}-06-30",
            'first_semester_start' => "{$yearStart}-09-01",
            'first_semester_end' => "{$yearStart}-12-31",
            'second_semester_start' => "{$yearEnd}-02-01",
            'second_semester_end' => "{$yearEnd}-06-30",
            'is_current' => false,
        ];
    }

    /**
     * Год, отмеченный как текущий в системе.
     */
    public function current(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_current' => true,
        ]);
    }
}
