<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceResource\Pages;
use App\Filament\Resources\ServiceResource\RelationManagers;
use App\Models\Service;
use App\Models\Category;
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
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->required()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Service Details')
                    ->schema([
                        Forms\Components\Select::make('hairlength')
                            ->required()
                            ->options([
                                'short' => 'Short',
                                'medium' => 'Medium',
                                'long' => 'Long',
                            ])
                            ->searchable(),
                        Forms\Components\TextInput::make('price')
                            ->required()
                            ->numeric()
                            ->prefix('€'),
                        Forms\Components\Toggle::make('active')
                            ->label('Active Status')
                            ->default(true)
                            ->onColor('success')
                            ->offColor('danger'),
                    ])
                    ->columns(3),
                
                Forms\Components\Section::make('Categories')
                    ->schema([
                        Forms\Components\Select::make('categories')
                            ->relationship('categories', 'name')
                            ->multiple()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\Toggle::make('active')
                                    ->default(true),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('hairlength')
                    ->sortable()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'short' => 'Short',
                        'medium' => 'Medium',
                        'long' => 'Long',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('categories.name')
                    ->badge()
                    ->color('success')
                    ->label('Categories')
                    ->bulleted(false)
                    ->searchable()
                    ->action(function ($record, $column, $state) {
                        // Get the category by name
                        $category = Category::where('name', $state)->first();
                        
                        if ($category) {
                            // Set the filter to this category by using closure reference to Livewire component
                            $livewire = $column->getTable()->getLivewire();
                            $livewire->tableFilters['categories']['values'] = [$category->id];
                        }
                    })
                    ->tooltip('Click to filter by this category'),
                Tables\Columns\TextColumn::make('description')
                    ->sortable(),
                Tables\Columns\TextColumn::make('price')
                    ->money('EUR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('active')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('categories')
                    ->relationship('categories', 'name')
                    ->multiple()
                    ->preload()
                    ->label('Filter by Category'),
                Tables\Filters\SelectFilter::make('hairlength')
                    ->options([
                        'short' => 'Short',
                        'medium' => 'Medium',
                        'long' => 'Long'
                    ])
                    ->label('Hair Length'),
                Tables\Filters\TernaryFilter::make('active')
                    ->label('Active Status')
                    ->placeholder('All Services')
                    ->trueLabel('Active Services')
                    ->falseLabel('Inactive Services')
                    ->boolean(),
                Tables\Filters\Filter::make('price_range')
                    ->form([
                        Forms\Components\Grid::make()
                            ->schema([
                                Forms\Components\TextInput::make('price_min')
                                    ->label('MIN (€)')
                                    ->numeric(),
                                Forms\Components\TextInput::make('price_max')
                                    ->label('MAX (€)')
                                    ->numeric()
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['price_min'],
                                fn (Builder $query, $price): Builder => $query->where('price', '>=', $price),
                            )
                            ->when(
                                $data['price_max'],
                                fn (Builder $query, $price): Builder => $query->where('price', '<=', $price),
                            );
                    }),
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
            //
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
