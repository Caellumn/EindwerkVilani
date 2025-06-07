<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BookingResource\Pages;
use App\Filament\Resources\BookingResource\RelationManagers;
use App\Models\Booking;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SelectColumn;
use Carbon\Carbon;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Notifications\Notification;

class BookingResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    
    protected static ?string $navigationLabel = 'Bookings';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Booking Information')
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
                                'regex:/^[a-zA-ZÀ-ÿ\s\'-]+$/u', // Allow letters, accents, spaces, apostrophes, hyphens
                            ])
                            ->validationMessages([
                                'required' => 'Voer uw volledige naam in.',
                                'min' => 'Naam moet minstens 2 tekens lang zijn.',
                                'max' => 'Naam mag niet langer zijn dan 255 tekens.',
                                'regex' => 'Naam mag alleen letters, spaties, apostrofen en koppeltekens bevatten.',
                            ]),
                            
                        Forms\Components\TextInput::make('email')
                            ->label('E-mailadres')
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->rules([
                                'required',
                                'email:rfc,dns',
                                'max:255',
                            ])
                            ->validationMessages([
                                'required' => 'Voer uw e-mailadres in.',
                                'email' => 'Voer een geldig e-mailadres in (bijvoorbeeld john@voorbeeld.nl).',
                                'max' => 'E-mailadres mag niet langer zijn dan 255 tekens.',
                            ]),
                            
                        Forms\Components\TextInput::make('telephone')
                            ->label('Telefoonnummer')
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->rules([
                                'required',
                                'regex:/^[\+]?[0-9\s\-\(\)]{10,20}$/', // Phone number format
                                'min:10',
                            ])
                            ->validationMessages([
                                'required' => 'Voer uw telefoonnummer in.',
                                'min' => 'Telefoonnummer moet minstens 10 cijfers lang zijn.',
                                'regex' => 'Voer een geldig telefoonnummer in. Je kunt formats gebruiken zoals: +1 234 567 8900, (123) 456-7890, of 0123456789',
                            ])
                            ->placeholder('bijvoorbeeld: +1 234 567 8900 of 0123456789'),
                            
                        Forms\Components\DateTimePicker::make('date')
                            ->label('Boekingsdatum en -tijd')
                            ->minDate(now())
                            ->rules([
                                'required',
                                'date',
                                'after_or_equal:now',
                            ])
                            ->validationMessages([
                                'required' => 'Selecteer een boekingsdatum en -tijd.',
                                'date' => 'Selecteer een geldige datum en tijd.',
                                'after_or_equal' => 'Boekingsdatum moet in de toekomst zijn. Selecteer een datum en tijd na nu.',
                            ])
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, $set, $get) {
                                // Recalculate end time if auto-calculation is enabled and services are selected
                                if ($get('auto_calculate_end_time') && $state && $get('services')) {
                                    $services = $get('services');
                                    $totalServiceTime = 0;
                                    
                                    if ($services && is_array($services) && count($services) > 0) {
                                        $totalServiceTime = (int) \App\Models\Service::whereIn('id', $services)->sum('time');
                                    }
                                    
                                    if ($totalServiceTime > 0) {
                                        $endTime = \Carbon\Carbon::parse($state)->addMinutes($totalServiceTime);
                                        $set('end_time', $endTime);
                                    } else {
                                        $set('end_time', $state);
                                    }
                                }
                            }),
                            
                        Forms\Components\DateTimePicker::make('end_time')
                            ->label('Eindtijd')
                            ->helperText('Laat leeg om de eindtijd automatisch te berekenen op basis van geselecteerde diensten')
                            ->minDate(now())
                            ->rules([
                                'nullable',
                                'date',
                                'after_or_equal:now',
                                'after_or_equal:date',
                            ])
                            ->validationMessages([
                                'date' => 'Selecteer een geldige einddatum en -tijd.',
                                'after_or_equal' => 'Eindtijd moet in de toekomst zijn en na de boekingsstarttijd.',
                            ])
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, $set, $get) {
                                // If user manually sets an end time, disable auto-calculation
                                if ($state) {
                                    $set('auto_calculate_end_time', false);
                                } else {
                                    // If user clears the end time, enable auto-calculation
                                    $set('auto_calculate_end_time', true);
                                    
                                    // Trigger recalculation immediately if we have date and services
                                    if ($get('date') && $get('services')) {
                                        $services = $get('services');
                                        $totalServiceTime = 0;
                                        
                                        if ($services && is_array($services) && count($services) > 0) {
                                            $totalServiceTime = (int) \App\Models\Service::whereIn('id', $services)->sum('time');
                                        }
                                        
                                        if ($totalServiceTime > 0) {
                                            $endTime = \Carbon\Carbon::parse($get('date'))->addMinutes($totalServiceTime);
                                            $set('end_time', $endTime);
                                        } else {
                                            $set('end_time', $get('date'));
                                        }
                                    }
                                }
                            }),

                        // Hidden field to track if end time should be auto-calculated
                        Forms\Components\Hidden::make('auto_calculate_end_time')
                            ->default(true),
                            
                        Forms\Components\Select::make('gender')
                            ->options([
                                'male' => 'Male',
                                'female' => 'Female',
                            ])
                            ->live()
                            ->rules([
                                'required',
                                'in:male,female',
                            ])
                            ->validationMessages([
                                'required' => 'Selecteer uw geslacht.',
                                'in' => 'Selecteer een geldig geslacht (Man of Vrouw).',
                            ]),
                            
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'confirmed' => 'Confirmed',
                                'cancelled' => 'Cancelled',
                                'completed' => 'Completed',
                            ])
                            ->live()
                            ->rules([
                                'required',
                                'in:pending,confirmed,cancelled,completed',
                            ])
                            ->validationMessages([
                                'required' => 'Selecteer een boekingsstatus.',
                                'in' => 'Selecteer een geldige status.',
                            ]),
                            
                        Forms\Components\Textarea::make('remarks')
                            ->maxLength(500)
                            ->columnSpanFull()
                            ->live(onBlur: true)
                            ->rows(3)
                            ->rules([
                                'nullable',
                                'string',
                                'max:500',
                            ])
                            ->validationMessages([
                                'max' => 'Opmerkingen mogen niet langer zijn dan 500 tekens. De huidige lengte is te lang.',
                                'string' => 'Opmerkingen moeten alleen tekst bevatten.',
                            ])
                            ->helperText('Optioneel: Voeg eventuele speciale opmerkingen of vereisten voor deze boeking toe.'),
                    ])
                    ->columns(2),
                    
                Forms\Components\Section::make('Services')
                    ->schema([
                        Forms\Components\CheckboxList::make('services')
                            ->relationship('services', 'name')
                            ->columns(2)
                            ->live()
                            ->rules([
                                'required',
                                'array',
                                'min:1',
                            ])
                            ->validationMessages([
                                'required' => 'Selecteer minstens één dienst voor deze boeking.',
                                'min' => 'Minstens één dienst moet worden geselecteerd.',
                                'array' => 'Services selectie is ongeldig.',
                            ])
                            ->helperText('Selecteer één of meer diensten voor deze boeking. De eindtijd wordt automatisch berekend.')
                            ->afterStateUpdated(function ($state, $set, $get) {
                                // Only auto-calculate if the flag is set (user hasn't manually set end time)
                                if ($get('auto_calculate_end_time') && $get('date')) {
                                    $totalServiceTime = 0;
                                    
                                    if ($state && is_array($state) && count($state) > 0) {
                                        // Calculate total time of selected services
                                        $totalServiceTime = (int) \App\Models\Service::whereIn('id', $state)->sum('time');
                                    }
                                    
                                    if ($totalServiceTime > 0) {
                                        $endTime = \Carbon\Carbon::parse($get('date'))->addMinutes($totalServiceTime);
                                        $set('end_time', $endTime);
                                    } else {
                                        // If no services selected, set end time same as start time
                                        $set('end_time', $get('date'));
                                    }
                                }
                            }),
                    ]),
                    
                Forms\Components\Section::make('Products')
                    ->schema([
                        Forms\Components\CheckboxList::make('products')
                            ->relationship('products', 'name')
                            ->label('Producten')
                            ->searchable()
                            ->searchPrompt('Zoek producten...')
                            ->searchingMessage('Zoeken...')
                            ->noSearchResultsMessage('Geen producten gevonden.')
                            ->columns(3)
                            ->live()
                            ->rules([
                                'nullable',
                                'array',
                            ])
                            ->validationMessages([
                                'array' => 'Product selectie is ongeldig.',
                            ])
                            ->helperText('Optioneel: Selecteer eventuele producten die tijdens deze boeking worden gebruikt.'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Naam')
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('date')
                    ->label('Starttijd')
                    ->dateTime('H:i')
                    ->sortable(),
                
                TextColumn::make('end_time')
                    ->label('Eindtijd')
                    ->dateTime('H:i')
                    ->sortable(),
                
                TextColumn::make('gender')
                    ->label('man/vrouw')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'male' => 'blue',
                        'female' => 'pink',
                    }),
                
                TextColumn::make('services.name')
                    ->label('Diensten')
                    ->listWithLineBreaks()
                    ->searchable()
                    ->limit(10)
                    ->tooltip(function (TextColumn $column): ?string {
                        $record = $column->getRecord();
                        $services = $record->services;
                        
                        if ($services->isEmpty()) {
                            return null;
                        }
                        
                        $serviceNames = $services->pluck('name')->join(', ');
                        
                        if (strlen($serviceNames) <= 10) {
                            return null;
                        }
                        
                        return $serviceNames;
                    }),
                    
                
                IconColumn::make('products_count')
                    ->label('Has Products')
                    ->boolean()
                    ->getStateUsing(function (Booking $record): bool {
                        return $record->products()->count() > 0;
                    }),
                
                Tables\Columns\SelectColumn::make('status')
                    ->options([
                        'pending' => 'In behandeling',
                        'confirmed' => 'Bevestigd',
                        'cancelled' => 'Geannuleerd',
                        'completed' => 'Voltooid',
                    ])
                    ->searchable()
                    ->sortable()
                    ->width('100px'),
                
                TextColumn::make('remarks')
                    ->label('Opmerkingen')
                    ->limit(7)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        
                        if (strlen($state) <= 7) {
                            return null;
                        }
                        
                        return $state;
                    }),
                    // add a column for the total price of the booking
                TextColumn::make('total_price')
                    ->label('Totaalprijs')
                    ->money('EUR')
                    ->sortable()
                    ->getStateUsing(function (Booking $record): float {
                        $servicesTotal = $record->services()->sum('price') ?? 0;
                        $productsTotal = $record->products()->sum('price') ?? 0;
                        return $servicesTotal + $productsTotal;
                    })
                    ->tooltip(function (Booking $record): string {
                        $servicesTotal = $record->services()->sum('price') ?? 0;
                        $productsTotal = $record->products()->sum('price') ?? 0;
                        $servicesCount = $record->services()->count();
                        $productsCount = $record->products()->count();
                        
                        return "Diensten ({$servicesCount}): €" . number_format($servicesTotal, 2) . 
                               "\nProducten ({$productsCount}): €" . number_format($productsTotal, 2) .
                               "\nTotaal: €" . number_format($servicesTotal + $productsTotal, 2);
                    }),
            ])
            ->filters([
                Tables\Filters\Filter::make('date')
                    ->form([
                        Forms\Components\DatePicker::make('date')
                            ->label('Viewing Bookings For')
                            ->default(now())
                            ->required(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['date'] ?? null,
                            fn (Builder $query, $date): Builder => $query->whereDate('date', $date)
                        );
                    })
                    ->indicateUsing(function (array $data): array {
                        if ($data['date'] ?? null) {
                            return [
                                'date' => 'Bookings for ' . Carbon::parse($data['date'])->format('F j, Y'),
                            ];
                        }
                        
                        return [];
                    })
                    ->default(fn(): array => ['date' => now()->format('Y-m-d')]),
                
                Tables\Filters\SelectFilter::make('gender')
                    ->label('Gender')
                    ->options([
                        'male' => 'Man',
                        'female' => 'Vrouw',
                    ])
                    ->placeholder('Alle geslachten'),
                
                Tables\Filters\TernaryFilter::make('exclude_cancelled')
                    ->label('Toon geannuleerde boekingen')
                    ->placeholder('Alle boekingen')
                    ->trueLabel('Inclusief geannuleerde')
                    ->falseLabel('Exclureer geannuleerde')
                    ->default(false) // Default to excluding cancelled bookings
                    ->queries(
                        true: fn (Builder $query): Builder => $query, // Show all including cancelled
                        false: fn (Builder $query): Builder => $query->where('status', '!=', 'cancelled'), // Exclude cancelled
                        blank: fn (Builder $query): Builder => $query // Show all when no filter applied
                    ),
            ])
            ->persistFiltersInSession()
            ->filtersFormColumns(3)
            ->filtersLayout(Tables\Enums\FiltersLayout::AboveContent)
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
            'index' => Pages\ListBookings::route('/'),
            'create' => Pages\CreateBooking::route('/create'),
            'edit' => Pages\EditBooking::route('/{record}/edit'),
        ];
    }
}
