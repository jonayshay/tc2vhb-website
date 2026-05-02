<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class SeasonFactory extends Factory
{
    public function definition(): array
    {
        $year = $this->faker->numberBetween(2024, 2030);

        return [
            'name'       => "{$year}-" . ($year + 1),
            'starts_at'  => "{$year}-09-01",
            'ends_at'    => ($year + 1) . '-06-30',
            'is_current' => false,
        ];
    }
}
