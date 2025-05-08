<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceResource\Pages;
use App\Filament\Resources\ServiceResource\RelationManagers;
use App\Models\Service;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ServiceResource extends Resource
{
    protected static ?string $model = Service::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->required()
                    ->maxLength(400),
                Forms\Components\TextInput::make('duration_phase_1')
                    ->required()
                    ->numeric()
                    ->label('Duration Phase 1 (minutes)'),
                Forms\Components\TextInput::make('rest_duration')
                    ->required()
                    ->numeric()
                    ->label('Rest Duration (minutes)'),
                Forms\Components\TextInput::make('duration_phase_2')
                    ->required()
                    ->numeric()
                    ->label('Duration Phase 2 (minutes)'),
                Forms\Components\Repeater::make('serviceWithHairlengths')
                    ->relationship()
                    ->schema([
                        Forms\Components\Select::make('hairlength_id')
                            ->relationship('hairlength', 'length')
                            ->required()
                            ->label('Hair Length'),
                        Forms\Components\TextInput::make('price')
                            ->required()
                            ->numeric()
                            ->prefix('€')
                            ->label('Price'),
                    ])
                    ->columns(2)
                    ->defaultItems(1)
                    ->reorderable(false)
                    ->columnSpanFull()
                    ->visible(fn ($livewire) => $livewire instanceof Pages\CreateService),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->searchable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('serviceWithHairlengths.hairlength.length')
                    ->label('Hair Length')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('serviceWithHairlengths.price')
                    ->label('Price')
                    ->money('EUR')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('hairlength')
                    ->relationship('serviceWithHairlengths.hairlength', 'length')
                    ->label('Filter by Hair Length')
                    ->multiple()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ServiceWithHairlengthsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServices::route('/'),
            'create' => Pages\CreateService::route('/create'),
            'edit' => Pages\EditService::route('/{record}/edit'),
        ];
    }
}
