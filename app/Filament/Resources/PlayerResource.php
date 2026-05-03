<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlayerResource\Pages;
use App\Models\Category;
use App\Models\Player;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PlayerResource extends Resource
{
    protected static ?string $model = Player::class;
    protected static ?string $navigationIcon = 'heroicon-o-identification';
    protected static ?string $navigationLabel = 'Joueurs';
    protected static ?string $modelLabel = 'Joueur';
    protected static ?string $pluralModelLabel = 'Joueurs';
    protected static ?string $navigationGroup = 'Équipes';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('category_id')
                ->label('Catégorie')
                ->options(Category::with('season')->get()->mapWithKeys(
                    fn (Category $c) => [$c->id => "{$c->season->name} — {$c->name}"]
                ))
                ->nullable(),

            Forms\Components\TextInput::make('last_name')
                ->label('Nom')
                ->required(),

            Forms\Components\TextInput::make('first_name')
                ->label('Prénom')
                ->required(),

            Forms\Components\DatePicker::make('birth_date')
                ->label('Date de naissance')
                ->required(),

            Forms\Components\TextInput::make('gender')
                ->label('Sexe')
                ->placeholder('M ou F')
                ->nullable(),

            Forms\Components\TextInput::make('license_number')
                ->label('Numéro de licence')
                ->nullable(),

            Forms\Components\FileUpload::make('photo')
                ->label('Photo')
                ->image()
                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                ->maxSize(2048)
                ->directory('players')
                ->disk('public')
                ->nullable(),

            Forms\Components\Toggle::make('has_image_rights')
                ->label('Droit à l\'image'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('last_name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('first_name')
                    ->label('Prénom')
                    ->searchable(),

                Tables\Columns\TextColumn::make('birth_date')
                    ->label('Naissance')
                    ->date('d/m/Y'),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('Catégorie')
                    ->placeholder('—'),

                Tables\Columns\IconColumn::make('has_image_rights')
                    ->label('Droit image')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Catégorie')
                    ->relationship('category', 'name'),

                Tables\Filters\TernaryFilter::make('has_image_rights')
                    ->label('Droit à l\'image'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPlayers::route('/'),
            'create' => Pages\CreatePlayer::route('/create'),
            'edit'   => Pages\EditPlayer::route('/{record}/edit'),
        ];
    }
}
