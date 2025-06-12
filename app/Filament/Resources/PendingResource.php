<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PendingResource\Pages;
use App\Filament\Resources\PendingResource\RelationManagers;
use App\Models\Booking;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Carbon\Carbon;

class PendingResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationLabel = 'Pendings';

    protected static ?string $modelLabel = 'Pending Booking';

    protected static ?string $pluralModelLabel = 'Pending Bookings';

    protected static ?int $navigationSort = 1;

    // Custom Eloquent query to show only pending bookings from today onwards
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('status', 'pending')
            ->whereDate('date', '>=', Carbon::today())
            ->orderBy('date', 'asc');
    }

    /**
     * Check if a booking has overlapping bookings with the same gender
     */
    public static function hasOverlappingBookings(Booking $booking): bool
    {
        // Check if booking has valid date and end_time
        if (!$booking->date || !$booking->end_time) {
            return false;
        }

        $startTime = Carbon::parse($booking->date);
        $endTime = Carbon::parse($booking->end_time);
        
        $overlappingBookings = Booking::where('gender', $booking->gender)
            ->where('id', '!=', $booking->id) // Exclude the current booking
            ->where('status', '!=', 'cancelled')
            ->where(function ($query) use ($startTime, $endTime) {
                $query->where(function ($q) use ($startTime, $endTime) {
                    // Current booking start time overlaps with existing booking
                    $q->where('date', '<=', $startTime)
                      ->where('end_time', '>', $startTime);
                })->orWhere(function ($q) use ($startTime, $endTime) {
                    // Current booking end time overlaps with existing booking
                    $q->where('date', '<', $endTime)
                      ->where('end_time', '>=', $endTime);
                })->orWhere(function ($q) use ($startTime, $endTime) {
                    // Existing booking is completely within current booking
                    $q->where('date', '>=', $startTime)
                      ->where('end_time', '<=', $endTime);
                })->orWhere(function ($q) use ($startTime, $endTime) {
                    // Current booking is completely within existing booking
                    $q->where('date', '<=', $startTime)
                      ->where('end_time', '>=', $endTime);
                });
            });

        return $overlappingBookings->exists();
    }

    /**
     * Get overlapping bookings details for a specific booking
     */
    public static function getOverlappingBookings(Booking $booking): array
    {
        // Check if booking has valid date and end_time
        if (!$booking->date || !$booking->end_time) {
            return [];
        }

        $startTime = Carbon::parse($booking->date);
        $endTime = Carbon::parse($booking->end_time);
        
        $overlappingBookings = Booking::where('gender', $booking->gender)
            ->where('id', '!=', $booking->id)
            ->where('status', '!=', 'cancelled')
            ->where(function ($query) use ($startTime, $endTime) {
                $query->where(function ($q) use ($startTime, $endTime) {
                    $q->where('date', '<=', $startTime)
                      ->where('end_time', '>', $startTime);
                })->orWhere(function ($q) use ($startTime, $endTime) {
                    $q->where('date', '<', $endTime)
                      ->where('end_time', '>=', $endTime);
                })->orWhere(function ($q) use ($startTime, $endTime) {
                    $q->where('date', '>=', $startTime)
                      ->where('end_time', '<=', $endTime);
                })->orWhere(function ($q) use ($startTime, $endTime) {
                    $q->where('date', '<=', $startTime)
                      ->where('end_time', '>=', $endTime);
                });
            })
            ->get(['id', 'name', 'date', 'end_time'])
            ->map(function ($overlappingBooking) {
                return [
                    'name' => $overlappingBooking->name,
                    'start_time' => Carbon::parse($overlappingBooking->date)->format('H:i'),
                    'end_time' => Carbon::parse($overlappingBooking->end_time)->format('H:i'),
                    'date' => Carbon::parse($overlappingBooking->date)->format('d-m-Y')
                ];
            })
            ->toArray();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Booking Details')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->disabled(),
                                Forms\Components\TextInput::make('email')
                                    ->email()
                                    ->required()
                                    ->disabled(),
                                Forms\Components\TextInput::make('telephone')
                                    ->tel()
                                    ->required()
                                    ->disabled(),
                                Forms\Components\Select::make('gender')
                                    ->options([
                                        'male' => 'Male',
                                        'female' => 'Female',
                                    ])
                                    ->required()
                                    ->disabled(),
                                Forms\Components\DateTimePicker::make('date')
                                    ->required()
                                    ->disabled(),
                                Forms\Components\DateTimePicker::make('end_time')
                                    ->disabled(),
                                Forms\Components\Select::make('status')
                                    ->options([
                                        'pending' => 'Pending',
                                        'confirmed' => 'Confirmed',
                                        'cancelled' => 'Cancelled',
                                        'completed' => 'Completed',
                                    ])
                                    ->required()
                                    ->default('pending'),
                                Forms\Components\Textarea::make('remarks')
                                    ->disabled(),
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
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('telephone')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('gender')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'male' => 'primary',
                        'female' => 'success',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                Tables\Columns\TextColumn::make('date')
                    ->dateTime('d-m-Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_time')
                    ->dateTime('H:i')
                    ->label('End Time'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'confirmed' => 'success',
                        'cancelled' => 'danger',
                        'completed' => 'secondary',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d-m-Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('gender')
                    ->options([
                        'male' => 'Male',
                        'female' => 'Female',
                    ]),
                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('date_from')
                                    ->default(Carbon::today()),
                                Forms\Components\DatePicker::make('date_to'),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['date_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date', '>=', $date),
                            )
                            ->when(
                                $data['date_to'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                // Show confirm action only if no overlapping bookings
                Tables\Actions\EditAction::make()
                    ->label('Confirm')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Booking $record): bool => !self::hasOverlappingBookings($record))
                    ->form([
                        Forms\Components\Select::make('status')
                            ->options([
                                'confirmed' => 'Confirmed',
                                'cancelled' => 'Cancelled',
                            ])
                            ->required()
                            ->default('confirmed'),
                    ])
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['status'] = $data['status'] ?? 'confirmed';
                        return $data;
                    }),
                // Show warning action only if there are overlapping bookings
                Tables\Actions\EditAction::make('warning_action')
                    ->label('Overlap Warning')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->color('warning')
                    ->visible(fn (Booking $record): bool => self::hasOverlappingBookings($record))
                    ->form([
                        Forms\Components\Placeholder::make('overlap_warning')
                            ->content(function (Booking $record): string {
                                $overlaps = self::getOverlappingBookings($record);
                                $content = "⚠️ **This booking overlaps with existing bookings:**\n\n";
                                
                                foreach ($overlaps as $overlap) {
                                    $content .= "• **{$overlap['name']}** on {$overlap['date']} from {$overlap['start_time']} to {$overlap['end_time']}\n";
                                }
                                
                                $content .= "\nPlease review carefully before confirming.";
                                
                                return $content;
                            }),
                        Forms\Components\Select::make('status')
                            ->options([
                                'confirmed' => 'Confirm Anyway',
                                'cancelled' => 'Cancel',
                            ])
                            ->required()
                            ->default('confirmed')
                            ->helperText('Choose carefully - confirming will create overlapping appointments.'),
                    ])
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['status'] = $data['status'] ?? 'confirmed';
                        return $data;
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('confirm_selected')
                        ->label('Confirm Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update(['status' => 'confirmed']);
                            });
                        }),
                    Tables\Actions\BulkAction::make('cancel_selected')
                        ->label('Cancel Selected')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update(['status' => 'cancelled']);
                            });
                        }),
                ]),
            ])
            ->defaultSort('date', 'asc');
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
            'index' => Pages\ListPendings::route('/'),
            'view' => Pages\ViewPending::route('/{record}'),
            'edit' => Pages\EditPending::route('/{record}/edit'),
        ];
    }
}
