<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Models\Category;
use App\Models\Season;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;
    protected static ?string $navigationIcon = 'heroicon-o-tag';
    protected static ?string $navigationLabel = 'Catégories';
    protected static ?string $modelLabel = 'Catégorie';
    protected static ?string $pluralModelLabel = 'Catégories';
    protected static ?string $navigationGroup = 'Équipes';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('season_id')
                ->label('Saison')
                ->options(Season::orderByDesc('name')->pluck('name', 'id'))
                ->required(),

            Forms\Components\Select::make('gender')
                ->label('Genre')
                ->options(['M' => 'Masculins', 'F' => 'Féminines', 'Mixte' => 'Mixte'])
                ->required(),

            Forms\Components\TextInput::make('name')
                ->label('Nom')
                ->required()
                ->live(debounce: 500)
                ->afterStateUpdated(function (Set $set, ?string $state, string $operation): void {
                    if ($operation === 'create') {
                        $set('slug', Str::slug($state ?? ''));
                    }
                }),

            Forms\Components\TextInput::make('slug')
                ->label('Slug (URL)')
                ->required(),

            Forms\Components\TextInput::make('birth_year_min')
                ->label('Année naissance min')
                ->numeric()
                ->required(),

            Forms\Components\TextInput::make('birth_year_max')
                ->label('Année naissance max')
                ->numeric()
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('season.name')
                    ->label('Saison')
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable(),

                Tables\Columns\TextColumn::make('gender')
                    ->label('Genre'),

                Tables\Columns\TextColumn::make('birth_year_min')
                    ->label('Nés entre')
                    ->formatStateUsing(fn (Category $record): string => "{$record->birth_year_min}–{$record->birth_year_max}"),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('season_id')
                    ->label('Saison')
                    ->relationship('season', 'name'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit'   => Pages\EditCategory::route('/{record}/edit'),
        ];
    }
}
