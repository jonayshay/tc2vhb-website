<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class BoardMemberFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'role' => $this->faker->randomElement(['Président', 'Vice-Président', 'Trésorier', 'Secrétaire', 'Membre CA']),
            'bio' => $this->faker->sentence(),
            'photo' => null,
            'sort_order' => 0,
        ];
    }
}
