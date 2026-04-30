<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ClubPresentationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(4),
            'accroche' => $this->faker->sentence(),
            'featured_image' => null,
            'content' => '<p>' . $this->faker->paragraph() . '</p>',
        ];
    }
}
