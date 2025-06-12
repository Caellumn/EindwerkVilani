<?php

namespace App\Filament\Resources\PendingResource\Pages;

use App\Filament\Resources\PendingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPending extends EditRecord
{
    protected static string $resource = PendingResource::class;

    protected ?string $heading = 'Update Booking Status';

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return PendingResource::getUrl('index');
    }
}
