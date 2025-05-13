<?php

namespace App\Filament\Resources\ServiceResource\Pages;

use App\Filament\Resources\ServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditService extends EditRecord
{
    protected static string $resource = ServiceResource::class;

    protected function getHeaderActions(): array
{
    return [
        Actions\DeleteAction::make()
            ->action(function ($record) {
                // Set active to 0 instead of deleting
                $record->update(['active' => 0]);
                
                // Redirect to the listing page
                return redirect()->to(ServiceResource::getUrl('index'));
            })
            ->requiresConfirmation()
            ->modalHeading('Deactivate Service')
            ->modalDescription('Are you sure you want to deactivate this service? This will set the active status to inactive.')
            ->modalSubmitActionLabel('Deactivate')
            ->successNotificationTitle('Service deactivated successfully'),
    ];
}
}
