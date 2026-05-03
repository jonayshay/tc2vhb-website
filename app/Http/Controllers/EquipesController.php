<?php

namespace App\Http\Controllers;

use App\Models\Season;
use Inertia\Inertia;
use Inertia\Response;

class EquipesController extends Controller
{
    public function index(): Response
    {
        $season = Season::where('is_current', true)->firstOrFail();

        $categories = $season->categories()
            ->orderBy('gender')
            ->orderBy('name')
            ->get();

        return Inertia::render('Equipes/Index', [
            'categories' => $categories,
        ]);
    }

    public function show(string $slug): Response
    {
        $season   = Season::where('is_current', true)->firstOrFail();
        $category = $season->categories()->where('slug', $slug)->firstOrFail();

        return Inertia::render('Equipes/Show', [
            'category' => $category,
            'teams'    => $category->teams()->get(),
            'players'  => $category->players()
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get(),
        ]);
    }
}
