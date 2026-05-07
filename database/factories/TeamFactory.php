<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class TeamFactory extends Factory
{
    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'name'        => 'Équipe ' . $this->faker->numberBetween(1, 3),
            'photo'       => null,
            'scorenco_id' => null,
        ];
    }
}
