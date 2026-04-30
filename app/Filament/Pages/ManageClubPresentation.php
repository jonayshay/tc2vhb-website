<?php

namespace App\Filament\Pages;

use App\Models\ClubPresentation;
use FilamentTiptapEditor\TiptapEditor;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ManageClubPresentation extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Présentation du club';
    protected static ?string $title = 'Présentation du club';
    protected static ?string $slug = 'club-presentation';
    protected static string $view = 'filament.pages.manage-club-presentation';

    public ?array $data = [];

    public function mount(): void
    {
        $presentation = ClubPresentation::firstOrCreate(
            ['id' => 1],
            [
                'title' => 'Présentation du club',
                'accroche' => 'Bienvenue au TC2V Handball',
                'featured_image' => null,
                'content' => '<p>À compléter.</p>',
            ]
        );

        $this->form->fill($presentation->toArray());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->label('Titre')
                    ->required(),

                Forms\Components\Textarea::make('accroche')
                    ->label('Accroche')
                    ->required()
                    ->columnSpanFull(),

                Forms\Components\FileUpload::make('featured_image')
                    ->label('Image mise en avant')
                    ->image()
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->maxSize(2048)
                    ->directory('club')
                    ->disk('public')
                    ->nullable(),

                TiptapEditor::make('content')
                    ->label('Contenu')
                    ->required()
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        ClubPresentation::updateOrCreate(['id' => 1], $data);

        Notification::make()
            ->title('Enregistré avec succès')
            ->success()
            ->send();
    }
}
