<?php

namespace App\Observers;

use App\Models\BoardMember;

class BoardMemberObserver
{
    public function creating(BoardMember $boardMember): void
    {
        if ($boardMember->sort_order === null || $boardMember->sort_order === 0) {
            $boardMember->sort_order = (BoardMember::max('sort_order') ?? 0) + 1;
        }
    }
}
