<?php

namespace App\Http\Controllers;

use App\Models\News;
use Inertia\Inertia;
use Inertia\Response;

class ActualitesController extends Controller
{
    public function index(): Response
    {
        $articles = News::where('status', 'published')
            ->orderBy('published_at', 'desc')
            ->paginate(12);

        return Inertia::render('Actualites/Index', [
            'articles' => $articles,
        ]);
    }

    public function show(string $slug): Response
    {
        $article = News::where('slug', $slug)
            ->where('status', 'published')
            ->firstOrFail();

        return Inertia::render('Actualites/Show', [
            'article' => $article,
        ]);
    }
}
