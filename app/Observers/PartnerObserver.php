<?php

namespace App\Observers;

use App\Models\Partner;

class PartnerObserver
{
    public function creating(Partner $partner): void
    {
        if ($partner->sort_order === null || $partner->sort_order === 0) {
            $partner->sort_order = Partner::max('sort_order') + 1;
        }
    }
}
