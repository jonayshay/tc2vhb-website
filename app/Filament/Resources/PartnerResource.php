<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PartnerResource\Pages;
use App\Models\Partner;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PartnerResource extends Resource
{
    protected static ?string $model = Partner::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    protected static ?string $navigationLabel = 'Partenaires';
    protected static ?string $modelLabel = 'Partenaire';
    protected static ?string $pluralModelLabel = 'Partenaires';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('Nom')
                ->required(),

            Forms\Components\FileUpload::make('logo')
                ->label('Logo')
                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml'])
                ->directory('partners/logos')
                ->disk('public')
                ->nullable(),

            Forms\Components\TextInput::make('url')
                ->label('Site web')
                ->url()
                ->nullable(),

            Forms\Components\Textarea::make('description')
                ->label('Description')
                ->nullable(),

            Forms\Components\TextInput::make('sort_order')
                ->label('Ordre')
                ->numeric()
                ->default(fn () => Partner::max('sort_order') + 1),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->columns([
                Tables\Columns\ImageColumn::make('logo')
                    ->label('Logo')
                    ->disk('public')
                    ->circular(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable(),

                Tables\Columns\TextColumn::make('url')
                    ->label('URL')
                    ->url(fn (?string $state): ?string => $state)
                    ->openUrlInNewTab(),

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
            'index' => Pages\ListPartners::route('/'),
            'create' => Pages\CreatePartner::route('/create'),
            'edit' => Pages\EditPartner::route('/{record}/edit'),
        ];
    }
}
