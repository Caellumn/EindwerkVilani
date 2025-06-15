<?php

namespace App\Filament\Resources\OpeningTimeResource\Pages;

use App\Filament\Resources\OpeningTimeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOpeningTimes extends ListRecords
{
    protected static string $resource = OpeningTimeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Add Opening Hours'),
        ];
    }
} 