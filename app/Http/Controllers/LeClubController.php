<?php

namespace App\Http\Controllers;

use App\Models\ClubPresentation;
use App\Models\StaffMember;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class LeClubController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('LeClub/Index');
    }

    public function presentation(): Response
    {
        $presentation = ClubPresentation::firstOrFail();

        return Inertia::render('LeClub/Presentation', [
            'presentation' => $presentation,
        ]);
    }

    public function entraineurs(): Response
    {
        $membres = StaffMember::where('type', 'entraineur')->get();

        return Inertia::render('LeClub/Entraineurs', [
            'groupes' => $this->grouperParCategorie($membres),
        ]);
    }

    public function arbitres(): Response
    {
        $membres = StaffMember::where('type', 'arbitre')->get();

        return Inertia::render('LeClub/Arbitres', [
            'groupes' => $this->grouperParCategorie($membres),
        ]);
    }

    private function grouperParCategorie(Collection $membres): array
    {
        $groupes = [];

        foreach (array_keys(StaffMember::CATEGORIES) as $slug) {
            $membresCategorie = $membres->filter(
                fn (StaffMember $m) => in_array($slug, $m->categories ?? [])
            );

            if ($membresCategorie->isNotEmpty()) {
                $groupes[] = [
                    'categorie' => StaffMember::CATEGORIES[$slug],
                    'membres' => $membresCategorie->values(),
                ];
            }
        }

        return $groupes;
    }
}
