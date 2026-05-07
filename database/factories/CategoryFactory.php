<?php

namespace Database\Factories;

use App\Models\Season;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CategoryFactory extends Factory
{
    public function definition(): array
    {
        $name = $this->faker->randomElement(['U9', 'U11', 'U13', 'U15', 'U17', 'Seniors'])
            . ' '
            . $this->faker->randomElement(['Masculins', 'Féminines', 'Mixte']);

        return [
            'season_id'      => Season::factory(),
            'name'           => $name,
            'slug'           => Str::slug($name),
            'gender'         => $this->faker->randomElement(['M', 'F', 'Mixte']),
            'type'           => 'youth',
            'birth_year_min' => 2010,
            'birth_year_max' => 2011,
        ];
    }
}
