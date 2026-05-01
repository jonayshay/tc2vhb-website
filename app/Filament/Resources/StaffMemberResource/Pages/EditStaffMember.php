<?php

namespace App\Filament\Resources\StaffMemberResource\Pages;

use App\Filament\Resources\StaffMemberResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStaffMember extends EditRecord
{
    protected static string $resource = StaffMemberResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
