<?php

namespace App\Http\Controllers;

use App\Models\Partner;
use Inertia\Inertia;
use Inertia\Response;

class PartenairesController extends Controller
{
    public function index(): Response
    {
        $partenaires = Partner::orderBy('sort_order')->get();

        return Inertia::render('Partenaires', [
            'partenaires' => $partenaires,
        ]);
    }
}
