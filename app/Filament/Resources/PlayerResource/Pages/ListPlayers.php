<?php

namespace App\Filament\Resources\PlayerResource\Pages;

use App\Filament\Resources\PlayerResource;
use App\Services\PlayerImportService;
use Filament\Actions;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListPlayers extends ListRecords
{
    protected static string $resource = PlayerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('importCsv')
                ->label('Importer CSV')
                ->icon('heroicon-o-arrow-up-tray')
                ->form([
                    FileUpload::make('csv_file')
                        ->label('Fichier CSV (export FFHandball)')
                        ->acceptedFileTypes(['text/csv', 'text/plain', 'application/csv'])
                        ->required()
                        ->storeFiles(false),
                ])
                ->action(function (array $data): void {
                    $result = (new PlayerImportService())->import($data['csv_file']->getRealPath());

                    Notification::make()
                        ->title('Import terminé')
                        ->body("{$result['imported']} importés · {$result['skipped']} doublons ignorés · {$result['unmatched']} sans catégorie")
                        ->success()
                        ->send();
                }),

            Actions\CreateAction::make(),
        ];
    }
}
