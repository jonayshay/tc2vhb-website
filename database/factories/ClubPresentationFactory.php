<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ClubPresentationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => 'Présentation du club',
            'accroche' => $this->faker->sentence(),
            'featured_image' => null,
            'content' => '<p>' . $this->faker->paragraph() . '</p>',
        ];
    }
}
