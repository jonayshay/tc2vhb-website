<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SeasonResource\Pages;
use App\Models\Season;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SeasonResource extends Resource
{
    protected static ?string $model = Season::class;
    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    protected static ?string $navigationLabel = 'Saisons';
    protected static ?string $modelLabel = 'Saison';
    protected static ?string $pluralModelLabel = 'Saisons';
    protected static ?string $navigationGroup = 'Équipes';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('Nom')
                ->required()
                ->placeholder('2026-2027'),

            Forms\Components\DatePicker::make('starts_at')
                ->label('Début'),

            Forms\Components\DatePicker::make('ends_at')
                ->label('Fin'),

            Forms\Components\Toggle::make('is_current')
                ->label('Saison courante'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable(),

                Tables\Columns\TextColumn::make('starts_at')
                    ->label('Début')
                    ->date('d/m/Y'),

                Tables\Columns\TextColumn::make('ends_at')
                    ->label('Fin')
                    ->date('d/m/Y'),

                Tables\Columns\TextColumn::make('is_current')
                    ->label('Courante')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Oui' : 'Non')
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
            ])
            ->actions([
                Tables\Actions\Action::make('setCurrent')
                    ->label('Définir comme saison courante')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Season $record): bool => ! $record->is_current)
                    ->requiresConfirmation()
                    ->action(fn (Season $record) => $record->update(['is_current' => true])),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSeasons::route('/'),
            'create' => Pages\CreateSeason::route('/create'),
            'edit'   => Pages\EditSeason::route('/{record}/edit'),
        ];
    }
}
