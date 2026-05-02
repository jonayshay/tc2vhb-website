<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BoardMemberResource\Pages;
use App\Models\BoardMember;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BoardMemberResource extends Resource
{
    protected static ?string $model = BoardMember::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Bureau & CA';
    protected static ?string $modelLabel = 'Membre du bureau';
    protected static ?string $pluralModelLabel = 'Bureau & CA';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('Nom')
                ->required(),

            Forms\Components\TextInput::make('role')
                ->label('Rôle')
                ->required(),

            Forms\Components\Textarea::make('bio')
                ->label('Bio')
                ->columnSpanFull()
                ->nullable(),

            Forms\Components\FileUpload::make('photo')
                ->label('Photo')
                ->image()
                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                ->maxSize(2048)
                ->directory('board')
                ->disk('public')
                ->nullable(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->columns([
                Tables\Columns\ImageColumn::make('photo')
                    ->label('Photo')
                    ->disk('public')
                    ->circular(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable(),

                Tables\Columns\TextColumn::make('role')
                    ->label('Rôle'),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Ordre')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBoardMembers::route('/'),
            'create' => Pages\CreateBoardMember::route('/create'),
            'edit' => Pages\EditBoardMember::route('/{record}/edit'),
        ];
    }
}
