<?php

namespace App\Observers;

use App\Models\Season;

class SeasonObserver
{
    public function updating(Season $season): void
    {
        if ($season->isDirty('is_current') && $season->is_current) {
            Season::where('id', '!=', $season->id)->update(['is_current' => false]);
        }
    }
}
