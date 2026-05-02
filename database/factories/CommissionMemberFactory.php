<?php

namespace Database\Factories;

use App\Models\Commission;
use Illuminate\Database\Eloquent\Factories\Factory;

class CommissionMemberFactory extends Factory
{
    public function definition(): array
    {
        return [
            'commission_id' => Commission::factory(),
            'name' => $this->faker->name(),
            'role' => $this->faker->randomElement(['Président(e)', 'Vice-Président(e)', 'Secrétaire', 'Membre']),
            'bio' => $this->faker->sentence(),
            'photo' => null,
            'sort_order' => 0,
        ];
    }
}
