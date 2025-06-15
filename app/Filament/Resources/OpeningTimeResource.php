<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OpeningTimeResource\Pages;
use App\Models\OpeningTime;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OpeningTimeResource extends Resource
{
    protected static ?string $model = OpeningTime::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationLabel = 'Opening Hours';

    protected static ?string $modelLabel = 'Opening Hours';

    protected static ?string $pluralModelLabel = 'Opening Hours';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationGroup = 'Settings';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Opening Hours Details')
                    ->schema([
                        Forms\Components\Select::make('day')
                            ->options([
                                'monday' => 'Monday',
                                'tuesday' => 'Tuesday',
                                'wednesday' => 'Wednesday',
                                'thursday' => 'Thursday',
                                'friday' => 'Friday',
                                'saturday' => 'Saturday',
                                'sunday' => 'Sunday',
                            ])
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->helperText('Select the day of the week'),
                            
                        Forms\Components\Select::make('status')
                            ->options([
                                'open' => 'Open',
                                'gesloten' => 'Gesloten',
                            ])
                            ->required()
                            ->live()
                            ->helperText('Is the salon open or closed on this day?'),
                            
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TimePicker::make('open')
                                    ->label('Opening Time')
                                    ->required(fn (Forms\Get $get): bool => $get('status') === 'open')
                                    ->visible(fn (Forms\Get $get): bool => $get('status') === 'open')
                                    ->seconds(false)
                                    ->helperText('When does the salon open?'),
                                    
                                Forms\Components\TimePicker::make('close')
                                    ->label('Closing Time')
                                    ->required(fn (Forms\Get $get): bool => $get('status') === 'open')
                                    ->visible(fn (Forms\Get $get): bool => $get('status') === 'open')
                                    ->seconds(false)
                                    ->after('open')
                                    ->helperText('When does the salon close?'),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('day')
                    ->label('Day')
                    ->sortable()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                    
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'open' => 'Open',
                        'gesloten' => 'Gesloten',
                        default => $state,
                    }),
                    
                Tables\Columns\TextColumn::make('open')
                    ->label('Opens')
                    ->formatStateUsing(function ($state, $record): string {
                        if ($record->status === 'gesloten') {
                            return '—';
                        }
                        return $state ? substr($state, 0, 5) : '—';
                    })
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('close')
                    ->label('Closes')
                    ->formatStateUsing(function ($state, $record): string {
                        if ($record->status === 'gesloten') {
                            return '—';
                        }
                        return $state ? substr($state, 0, 5) : '—';
                    })
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('formatted_hours')
                    ->label('Opening Hours')
                    ->state(function (OpeningTime $record): string {
                        return $record->getFormattedHours();
                    }),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('day')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'open' => 'Open Days',
                        'gesloten' => 'Gesloten Days',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation(),
                ]),
            ])
            ->emptyStateHeading('No opening hours configured')
            ->emptyStateDescription('Configure your salon\'s opening hours for each day of the week.')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
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
            'index' => Pages\ListOpeningTimes::route('/'),
            'create' => Pages\CreateOpeningTime::route('/create'),
            'edit' => Pages\EditOpeningTime::route('/{record}/edit'),
        ];
    }
} 