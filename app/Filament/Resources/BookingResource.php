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
                            ->required()
                            ->maxLength(255),
                            
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255),
                            
                        Forms\Components\TextInput::make('telephone')
                            ->tel()
                            ->required()
                            ->maxLength(255),
                            
                        Forms\Components\DateTimePicker::make('date')
                            ->required()
                            ->minDate(now())
                            ->rule('after_or_equal:now')
                            ->validationMessages([
                                'after_or_equal' => 'The booking date must be in the future.',
                            ]),
                            
                        Forms\Components\DateTimePicker::make('end_time')
                            ->label('End Time')
                            ->helperText('This will be automatically calculated based on selected services')
                            ->minDate(now())
                            ->rules([
                                'after_or_equal:now',
                                'after_or_equal:date',
                            ])
                            ->validationMessages([
                                'after_or_equal' => 'The end time must be in the future and after the start time.',
                            ]),
                            
                        Forms\Components\Select::make('gender')
                            ->options([
                                'male' => 'Male',
                                'female' => 'Female',
                            ])
                            ->required(),
                            
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'confirmed' => 'Confirmed',
                                'cancelled' => 'Cancelled',
                                'completed' => 'Completed',
                            ])
                            ->required(),
                            
                        Forms\Components\Textarea::make('remarks')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                    
                Forms\Components\Section::make('Services')
                    ->schema([
                        Forms\Components\CheckboxList::make('services')
                            ->relationship('services', 'name')
                            ->columns(2)
                            ->searchable()
                            ->afterStateUpdated(function ($state, $record) {
                                // Only recalculate if we have an existing record (editing)
                                if ($record && $record->exists) {
                                    $record->recalculateEndTime();
                                }
                            }),
                    ]),
                    
                Forms\Components\Section::make('Products')
                    ->schema([
                        Forms\Components\CheckboxList::make('products')
                            ->relationship('products', 'name')
                            ->columns(2)
                            ->searchable(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('date')
                    ->dateTime('d-m-Y H:i')
                    ->sortable(),
                
                TextColumn::make('end_time')
                    ->label('End Time')
                    ->dateTime('d-m-Y H:i')
                    ->sortable(),
                
                TextColumn::make('gender')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'male' => 'blue',
                        'female' => 'pink',
                    }),
                
                TextColumn::make('services.name')
                    ->listWithLineBreaks()
                    ->searchable(),
                
                IconColumn::make('products_count')
                    ->label('Has Products')
                    ->boolean()
                    ->getStateUsing(function (Booking $record): bool {
                        return $record->products()->count() > 0;
                    }),
                
                Tables\Columns\SelectColumn::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed',
                        'cancelled' => 'Cancelled',
                        'completed' => 'Completed',
                    ])
                    ->searchable()
                    ->sortable()
                    ->width('100px'),
                
                TextColumn::make('remarks')
                    ->limit(20)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        
                        if (strlen($state) <= 20) {
                            return null;
                        }
                        
                        return $state;
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
                        'male' => 'Male',
                        'female' => 'Female',
                    ])
                    ->placeholder('All Genders'),
                
                Tables\Filters\TernaryFilter::make('exclude_cancelled')
                    ->label('Show Cancelled Bookings')
                    ->placeholder('All bookings')
                    ->trueLabel('Include cancelled')
                    ->falseLabel('Exclude cancelled')
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
