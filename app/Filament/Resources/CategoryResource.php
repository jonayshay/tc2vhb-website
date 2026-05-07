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
use Filament\Tables\Actions\ReplicateAction;
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

            Forms\Components\Select::make('type')
                ->label('Type')
                ->options(['youth' => 'Jeunes', 'senior' => 'Séniors', 'loisirs' => 'Loisirs'])
                ->default('youth')
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
                ->numeric(),

            Forms\Components\TextInput::make('birth_year_max')
                ->label('Année naissance max')
                ->numeric(),
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

                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'youth'   => 'Jeunes',
                        'senior'  => 'Séniors',
                        'loisirs' => 'Loisirs',
                        default   => $state,
                    }),

                Tables\Columns\TextColumn::make('birth_year_min')
                    ->label('Nés entre')
                    ->formatStateUsing(function (Category $record): string {
                        if ($record->birth_year_min === null && $record->birth_year_max === null) {
                            return '—';
                        }
                        return "{$record->birth_year_min}–{$record->birth_year_max}";
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('season_id')
                    ->label('Saison')
                    ->relationship('season', 'name'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                ReplicateAction::make()
                    ->label('Dupliquer')
                    ->excludeAttributes(['slug'])
                    ->beforeReplicaSaved(function (Category $replica): void {
                        $base = \Illuminate\Support\Str::slug($replica->name) . '-copie';
                        $slug = $base;
                        $i    = 2;
                        while (Category::where('slug', $slug)->where('season_id', $replica->season_id)->exists()) {
                            $slug = $base . '-' . $i++;
                        }
                        $replica->slug = $slug;
                    }),
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
