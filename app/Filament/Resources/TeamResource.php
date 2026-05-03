<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TeamResource\Pages;
use App\Models\Category;
use App\Models\Team;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TeamResource extends Resource
{
    protected static ?string $model = Team::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Équipes';
    protected static ?string $modelLabel = 'Équipe';
    protected static ?string $pluralModelLabel = 'Équipes';
    protected static ?string $navigationGroup = 'Équipes';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('category_id')
                ->label('Catégorie')
                ->options(Category::with('season')->get()->mapWithKeys(
                    fn (Category $c) => [$c->id => "{$c->season->name} — {$c->name}"]
                ))
                ->required(),

            Forms\Components\TextInput::make('name')
                ->label('Nom')
                ->required()
                ->placeholder('Équipe 1'),

            Forms\Components\FileUpload::make('photo')
                ->label('Photo d\'équipe')
                ->image()
                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                ->maxSize(4096)
                ->directory('teams')
                ->disk('public')
                ->nullable(),

            Forms\Components\TextInput::make('scorenco_id')
                ->label('ID Scorenco')
                ->nullable(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('photo')
                    ->label('Photo')
                    ->disk('public'),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable(),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('Catégorie')
                    ->sortable(),

                Tables\Columns\TextColumn::make('scorenco_id')
                    ->label('Scorenco ID')
                    ->placeholder('—'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Catégorie')
                    ->relationship('category', 'name'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListTeams::route('/'),
            'create' => Pages\CreateTeam::route('/create'),
            'edit'   => Pages\EditTeam::route('/{record}/edit'),
        ];
    }
}
