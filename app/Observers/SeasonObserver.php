<?php

namespace App\Observers;

use App\Models\Season;

class SeasonObserver
{
    public function creating(Season $season): void
    {
        if ($season->is_current) {
            Season::query()->update(['is_current' => false]);
        }
    }

    public function updating(Season $season): void
    {
        if ($season->isDirty('is_current') && $season->is_current) {
            Season::where('id', '!=', $season->id)->update(['is_current' => false]);
        }
    }
}
