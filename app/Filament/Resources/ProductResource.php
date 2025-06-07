<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Naam')
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->rules([
                        'required',
                        'string',
                        'max:255',
                        'min:2',
                        'regex:/^[a-zA-ZÀ-ÿ0-9\s\'-\.]+$/u', // Allow letters, numbers, spaces, apostrophes, hyphens, dots
                    ])
                    ->validationMessages([
                        'required' => 'Voer de productnaam in.',
                        'min' => 'Productnaam moet minstens 2 tekens lang zijn.',
                        'max' => 'Productnaam mag niet langer zijn dan 255 tekens.',
                        'regex' => 'Productnaam mag alleen letters, cijfers, spaties, apostrofen, streepjes en punten bevatten.',
                    ])
                    ->placeholder('b.v., Premium Haar Shampoo'),
                    
                Forms\Components\Textarea::make('description')
                    ->label('Beschrijving')
                    ->maxLength(400)
                    ->rows(4)
                    ->live(onBlur: true)
                    ->rules([
                        'required',
                        'string',
                        'max:400',
                        'min:10',
                    ])
                    ->validationMessages([
                        'required' => 'Voer een productbeschrijving in.',
                        'min' => 'Beschrijving moet minstens 10 tekens lang zijn.',
                        'max' => 'Beschrijving mag niet langer zijn dan 400 tekens.',
                    ])
                    ->helperText('Beschrijf de productkenmerken en voordelen (10-400 tekens)')
                    ->placeholder('Voer een uitgebreide beschrijving van het product in...'),
                    
                Forms\Components\TextInput::make('price')
                    ->label('Prijs')
                    ->live(onBlur: true)
                    ->rules([
                        'required',
                        'numeric',
                        'min:0.01',
                        'max:999999.99',
                        'regex:/^\d+(\.\d{1,2})?$/', // Allow decimal with up to 2 places
                    ])
                    ->validationMessages([
                        'required' => 'Voer de productprijs in.',
                        'numeric' => 'Prijs moet een geldig getal zijn.',
                        'min' => 'Prijs moet minstens €0.01 zijn.',
                        'max' => 'Prijs mag niet hoger zijn dan €999,999.99.',
                        'regex' => 'Prijsformaat is ongeldig. Gebruik het formaat: 19.99',
                    ])
                    ->prefix('€')
                    ->placeholder('0.00')
                    ->helperText('Voer de prijs in euro\'s (b.v., 19.99)'),
                    
                Forms\Components\TextInput::make('stock')
                    ->label('Voorraad')
                    ->live(onBlur: true)
                    ->rules([
                        'required',
                        'integer',
                        'min:0',
                        'max:99999',
                    ])
                    ->validationMessages([
                        'required' => 'Voer de voorraadhoeveelheid in.',
                        'integer' => 'Voorraad moet een geheel getal zijn.',
                        'min' => 'Voorraad mag niet negatief zijn.',
                        'max' => 'Voorraad mag niet hoger zijn dan 99,999 eenheden.',
                    ])
                    ->suffix('eenheden')
                    ->placeholder('0')
                    ->helperText('Huidige voorraad'),
                    
                Forms\Components\TextInput::make('image')
                    ->label('Afbeelding URL')
                    ->nullable()
                    ->live(onBlur: true)
                    ->rules([
                        'nullable',
                        'url',
                        'max:2048',
                        'regex:/^https?:\/\/.+\.(jpg|jpeg|png|gif|webp)(\?.*)?$/i', // Valid image URL
                    ])
                    ->validationMessages([
                        'url' => 'Voer een geldige URL in die begint met http:// of https://',
                        'max' => 'URL mag niet langer zijn dan 2048 tekens.',
                        'regex' => 'URL moet naar een geldige afbeelding bestand (jpg, jpeg, png, gif, webp) verwijzen.',
                    ])
                    ->helperText('De Cloudinary URL verschijnt hier na upload')
                    ->placeholder('https://example.com/image.jpg')
                    ->afterStateUpdated(function ($state) {
                        // This will trigger when the field value changes
                    }),

                Forms\Components\Placeholder::make('upload_section')
                    ->label('Upload Afbeelding naar Cloudinary')
                    ->content(view('filament.components.cloudinary-upload'))
                    ->hiddenOn('view'),
                    
                Forms\Components\Section::make('Categories')
                    ->label('Categorieën')
                    ->schema([
                        Forms\Components\Select::make('categories')
                            ->relationship('categories', 'name')
                            ->multiple()
                            ->preload()
                            ->live()
                            ->rules([
                                'nullable',
                                'array',
                            ])
                            ->validationMessages([
                                'array' => 'Categories selection is invalid.',
                            ])
                                ->helperText('Optioneel: Selecteer één of meer categorieën voor dit product')
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
                //
                Tables\Columns\TextColumn::make('name')->sortable()->searchable()->label('Naam'),
                Tables\Columns\TextColumn::make('price')->sortable()->searchable()->label('Prijs'),
                Tables\Columns\TextColumn::make('stock')->sortable()->searchable()->label('Voorraad'),
                Tables\Columns\IconColumn::make('active')
                    ->boolean()
                    ->label('Status')
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                Tables\Columns\TextColumn::make('categories.name')
                    ->badge()
                    ->color('success')
                    ->label('Categorieën')
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
                    ->tooltip('Klik om deze categorie te filteren'),
                Tables\Columns\ImageColumn::make('image')->label('Afbeelding'),
                Tables\Columns\TextColumn::make('description')->sortable()->searchable()->limit(20)->suffix('...')->label('Beschrijving'),

            ])
            ->filters([ 
                //
                Tables\Filters\SelectFilter::make('categories')
                    ->relationship('categories', 'name')
                    ->multiple()
                    ->preload()
                    ->label('Filter by Categorie'),
                Tables\Filters\TernaryFilter::make('active')
                    ->label('Active Status')
                    ->placeholder('Alle Producten')
                    ->trueLabel('Actieve Producten')
                    ->falseLabel('Verwijderde Producten')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->where('active', 1),
                        false: fn (Builder $query): Builder => $query->where('active', 0),
                        blank: fn (Builder $query): Builder => $query
                    )
                    ->default(true)
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('delete')
                    ->label('Verwijder')
                    ->color('danger')
                    ->icon('heroicon-o-trash')
                    ->requiresConfirmation()
                    ->action(function (Product $record) {
                        $record->update(['active' => 0]);
                    })
                    ->visible(fn (Product $record): bool => $record->active == 1), 
                Tables\Actions\Action::make('restore')
                    ->label('Herstel')
                    ->color('success')
                    ->icon('heroicon-o-arrow-uturn-up')
                    ->requiresConfirmation()
                    ->action(function (Product $record) {
                        $record->update(['active' => 1]);
                    })
                    ->visible(fn (Product $record): bool => $record->active == 0),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('delete')
                        ->label('Verwijder')
                        ->color('danger')
                        ->icon('heroicon-o-trash')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $records->each(function ($record) {
                                $record->update(['active' => 0]);
                            });
                        }),
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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
