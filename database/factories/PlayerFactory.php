<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlayerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'category_id'      => Category::factory(),
            'last_name'        => $this->faker->lastName(),
            'first_name'       => $this->faker->firstName(),
            'birth_date'       => $this->faker->dateTimeBetween('-20 years', '-8 years')->format('Y-m-d'),
            'gender'           => $this->faker->randomElement(['M', 'F']),
            'license_number'   => null,
            'photo'            => null,
            'has_image_rights' => false,
        ];
    }
}
