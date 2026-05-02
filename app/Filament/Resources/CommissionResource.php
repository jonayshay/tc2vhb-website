<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CommissionResource\Pages;
use App\Models\Commission;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CommissionResource extends Resource
{
    protected static ?string $model = Commission::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-circle';
    protected static ?string $navigationLabel = 'Commissions';
    protected static ?string $modelLabel = 'Commission';
    protected static ?string $pluralModelLabel = 'Commissions';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('Nom')
                ->required(),

            Forms\Components\Textarea::make('description')
                ->label('Description')
                ->columnSpanFull()
                ->nullable(),

            Forms\Components\Repeater::make('members')
                ->label('Membres')
                ->relationship('members')
                ->reorderableWithDragAndDrop()
                ->columnSpanFull()
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nom')
                        ->required(),

                    Forms\Components\TextInput::make('role')
                        ->label('Rôle')
                        ->required(),

                    Forms\Components\Textarea::make('bio')
                        ->label('Bio')
                        ->nullable(),

                    Forms\Components\FileUpload::make('photo')
                        ->label('Photo')
                        ->image()
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                        ->maxSize(2048)
                        ->directory('commissions')
                        ->disk('public')
                        ->nullable(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable(),

                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->limit(60),

                Tables\Columns\TextColumn::make('members_count')
                    ->label('Membres')
                    ->counts('members'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCommissions::route('/'),
            'create' => Pages\CreateCommission::route('/create'),
            'edit' => Pages\EditCommission::route('/{record}/edit'),
        ];
    }
}
