<?php

namespace Database\Seeders;

use App\Models\ClubPresentation;
use Illuminate\Database\Seeder;

class ClubPresentationSeeder extends Seeder
{
    public function run(): void
    {
        ClubPresentation::firstOrCreate(
            ['id' => 1],
            [
                'title' => 'Présentation du club',
                'accroche' => 'Bienvenue au TC2V Handball',
                'featured_image' => null,
                'content' => '<p>À compléter.</p>',
            ]
        );
    }
}
