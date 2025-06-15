<?php

namespace App\Filament\Resources\OpeningTimeResource\Pages;

use App\Filament\Resources\OpeningTimeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOpeningTime extends EditRecord
{
    protected static string $resource = OpeningTimeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
} 