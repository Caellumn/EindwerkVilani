<?php

namespace App\Filament\Resources\PendingResource\Pages;

use App\Filament\Resources\PendingResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPending extends ViewRecord
{
    protected static string $resource = PendingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('confirm')
                ->label('Confirm Booking')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update(['status' => 'confirmed']);
                    $this->redirect(PendingResource::getUrl('index'));
                })
                ->visible(fn () => $this->record->status === 'pending'),
            Actions\Action::make('cancel')
                ->label('Cancel Booking')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update(['status' => 'cancelled']);
                    $this->redirect(PendingResource::getUrl('index'));
                })
                ->visible(fn () => $this->record->status === 'pending'),
            Actions\EditAction::make(),
        ];
    }
}
