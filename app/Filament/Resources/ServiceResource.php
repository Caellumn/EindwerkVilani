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
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->rules([
                                'required',
                                'string',
                                'max:255',
                                'min:2',
                                'regex:/^[a-zA-ZÀ-ÿ0-9\s\'-\.\/\(\)]+$/u', // Allow letters, numbers, spaces, common punctuation
                            ])
                            ->validationMessages([
                                'required' => 'Please enter the service name.',
                                'min' => 'Service name must be at least 2 characters long.',
                                'max' => 'Service name cannot exceed 255 characters.',
                                'regex' => 'Service name can only contain letters, numbers, spaces, and common punctuation.',
                            ])
                            ->placeholder('e.g., Hair Cut & Style'),
                            
                        Forms\Components\Textarea::make('description')
                            ->columnSpanFull()
                            ->rows(4)
                            ->live(onBlur: true)
                            ->rules([
                                'required',
                                'string',
                                'min:10',
                                'max:1000',
                            ])
                            ->validationMessages([
                                'required' => 'Please enter a service description.',
                                'min' => 'Description must be at least 10 characters long.',
                                'max' => 'Description cannot exceed 1000 characters.',
                            ])
                            ->helperText('Describe what this service includes and any special details (10-1000 characters)')
                            ->placeholder('Enter a detailed description of the service...'),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Service Details')
                    ->schema([
                        Forms\Components\Select::make('hairlength')
                            ->options([
                                'short' => 'Short',
                                'medium' => 'Medium',
                                'long' => 'Long',
                            ])
                            ->searchable()
                            ->live()
                            ->rules([
                                'required',
                                'in:short,medium,long',
                            ])
                            ->validationMessages([
                                'required' => 'Please select the hair length category.',
                                'in' => 'Please select a valid hair length option.',
                            ])
                            ->helperText('Select the hair length this service is designed for'),
                            
                        Forms\Components\TextInput::make('price')
                            ->numeric()
                            ->prefix('€')
                            ->live(onBlur: true)
                            ->rules([
                                'required',
                                'numeric',
                                'min:0.01',
                                'max:999999.99',
                                'regex:/^\d+(\.\d{1,2})?$/', // Allow decimal with up to 2 places
                            ])
                            ->validationMessages([
                                'required' => 'Please enter the service price.',
                                'numeric' => 'Price must be a valid number.',
                                'min' => 'Price must be at least €0.01.',
                                'max' => 'Price cannot exceed €999,999.99.',
                                'regex' => 'Price format is invalid. Use format like: 45.50',
                            ])
                            ->placeholder('0.00')
                            ->helperText('Enter price in euros (e.g., 45.50)'),
                            
                        Forms\Components\TextInput::make('time')
                            ->label('Duration (minutes)')
                            ->numeric()
                            ->suffix('min')
                            ->live(onBlur: true)
                            ->rules([
                                // 'required',
                                'integer',
                                'min:1',
                                'max:480', // 8 hours max
                            ])
                            ->validationMessages([
                                'required' => 'Please enter the service duration.',
                                'integer' => 'Duration must be a whole number.',
                                'min' => 'Duration must be at least 1 minute.',
                                'max' => 'Duration cannot exceed 480 minutes (8 hours).',
                            ])
                            ->helperText('standaard op 30 minuten')
                            ->placeholder('30'),
                            
                        Forms\Components\Toggle::make('active')
                            ->label('Active Status')
                            ->default(true)
                            ->onColor('success')
                            ->offColor('danger')
                            ->rules([
                                'boolean',
                            ])
                            ->validationMessages([
                                'boolean' => 'Active status must be true or false.',
                            ])
                            ->helperText('Toggle to activate or deactivate this service'),
                    ])
                    ->columns(3),
                
                Forms\Components\Section::make('Categories')
                    ->schema([
                        Forms\Components\Select::make('categories')
                            ->relationship('categories', 'name')
                            ->multiple()
                            ->preload()
                            ->live()
                            ->helperText('💡 Tip: Adding categories helps customers find your services more easily')
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->maxLength(255)
                                    ->rules([
                                        'required',
                                        'string',
                                        'max:255',
                                        'min:2',
                                    ])
                                    ->validationMessages([
                                        'required' => 'Please enter the category name.',
                                        'min' => 'Category name must be at least 2 characters long.',
                                        'max' => 'Category name cannot exceed 255 characters.',
                                    ]),
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
                    ->label('Naam')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('hairlength')
                    ->label('Haarlengte')
                    ->sortable()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'short' => 'Short',
                        'medium' => 'Medium',
                        'long' => 'Long',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('categories.name')
                    ->label('Categorieën')
                    ->badge()
                    ->color('success')
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
                    ->label('Beschrijving')
                    ->limit(10)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        
                        if (strlen($state) <= 10) {
                            return null;
                        }
                        
                        return $state;
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('price')
                    ->label('Prijs')
                    ->money('EUR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('time')
                    ->label('Duur')
                    ->suffix(' min')
                    ->sortable(),
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
                    ->label('Haarlengte'),
                Tables\Filters\TernaryFilter::make('active')
                    ->label('Actief')
                    ->placeholder('Alle diensten')  // Changed from 'All Services' to be clear this is the default view
                    ->trueLabel('Actieve diensten')
                    ->falseLabel('Inactieve diensten')
                    ->boolean()
                    ->default(true)  // This makes "Active Services" the default selection
                    ->queries(
                        true: fn (Builder $query): Builder => $query->where('active', 1),
                        false: fn (Builder $query): Builder => $query->where('active', 0),
                        blank: fn (Builder $query): Builder => $query  // Show all when "All" is selected
                    ),
                Tables\Filters\Filter::make('price_range')
                    ->label('Prijs')
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
