<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class StaffMemberFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'type' => $this->faker->randomElement(['entraineur', 'arbitre']),
            'photo' => null,
            'bio' => $this->faker->sentence(),
            'categories' => ['u13_m'],
        ];
    }

    public function entraineur(): static
    {
        return $this->state(['type' => 'entraineur']);
    }

    public function arbitre(): static
    {
        return $this->state(['type' => 'arbitre']);
    }
}
