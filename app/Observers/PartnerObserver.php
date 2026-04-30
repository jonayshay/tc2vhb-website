<?php

namespace App\Observers;

use App\Models\Partner;

class PartnerObserver
{
    public function creating(Partner $partner): void
    {
        if (empty($partner->sort_order)) {
            $partner->sort_order = Partner::max('sort_order') + 1;
        }
    }
}
