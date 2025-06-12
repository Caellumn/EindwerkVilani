<?php

namespace App\Filament\Resources\PendingResource\Pages;

use App\Filament\Resources\PendingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPendings extends ListRecords
{
    protected static string $resource = PendingResource::class;

    protected ?string $heading = 'Pending Bookings';
    
    protected ?string $subheading = 'Manage bookings that are waiting for confirmation';

    protected function getHeaderActions(): array
    {
        return [
            // No create action - pending bookings come from API/bookings
        ];
    }
}
