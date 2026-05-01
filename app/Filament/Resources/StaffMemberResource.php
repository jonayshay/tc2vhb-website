<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StaffMemberResource\Pages;
use App\Models\StaffMember;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class StaffMemberResource extends Resource
{
    protected static ?string $model = StaffMember::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Staff';
    protected static ?string $modelLabel = 'Membre du staff';
    protected static ?string $pluralModelLabel = 'Staff';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('Nom')
                ->required(),

            Forms\Components\Select::make('type')
                ->label('Type')
                ->options([
                    'entraineur' => 'Entraîneur',
                    'arbitre' => 'Arbitre',
                ])
                ->required(),

            Forms\Components\FileUpload::make('photo')
                ->label('Photo')
                ->image()
                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                ->maxSize(2048)
                ->directory('staff/photos')
                ->disk('public')
                ->nullable(),

            Forms\Components\Textarea::make('bio')
                ->label('Bio')
                ->columnSpanFull()
                ->nullable(),

            Forms\Components\CheckboxList::make('categories')
                ->label('Catégories')
                ->options(StaffMember::CATEGORIES)
                ->columns(2)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('photo')
                    ->label('Photo')
                    ->disk('public')
                    ->circular(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'entraineur' => 'primary',
                        'arbitre' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'entraineur' => 'Entraîneur',
                        'arbitre' => 'Arbitre',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('categories')
                    ->label('Catégories')
                    ->formatStateUsing(fn ($state): string =>
                        is_array($state)
                            ? implode(', ', array_map(
                                fn ($slug) => StaffMember::CATEGORIES[$slug] ?? $slug,
                                $state
                            ))
                            : (string) $state
                    ),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        'entraineur' => 'Entraîneur',
                        'arbitre' => 'Arbitre',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStaffMembers::route('/'),
            'create' => Pages\CreateStaffMember::route('/create'),
            'edit' => Pages\EditStaffMember::route('/{record}/edit'),
        ];
    }
}
