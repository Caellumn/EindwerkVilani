<?php

namespace App\Filament\Resources\BookingResource\Pages;

use App\Filament\Resources\BookingResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Models\Booking;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use App\Notifications\BookingCreated;
use App\Notifications\BookingConfirmed;
use App\Notifications\SimpleNotifiable;
use Illuminate\Support\Facades\Notification as LaravelNotification;

class CreateBooking extends CreateRecord
{
    protected static string $resource = BookingResource::class;
    
    protected bool $overlapConfirmed = false;
    protected bool $shouldAutoCalculateEndTime = true;

    protected function beforeCreate(): void
    {
        // If overlap was already confirmed, skip checking
        if ($this->overlapConfirmed) {
            return;
        }

        // Get the form data
        $data = $this->form->getState();
        
        // Check for overlapping bookings
        $startTime = Carbon::parse($data['date']);
        $endTime = Carbon::parse($data['end_time']);
        
        $overlappingBookings = Booking::where('gender', $data['gender'])
            ->where('status', '!=', 'cancelled')
            ->where(function ($query) use ($startTime, $endTime) {
                $query->where(function ($q) use ($startTime, $endTime) {
                    // New booking start time overlaps with existing booking
                    $q->where('date', '<=', $startTime)
                      ->where('end_time', '>', $startTime);
                })->orWhere(function ($q) use ($startTime, $endTime) {
                    // New booking end time overlaps with existing booking
                    $q->where('date', '<', $endTime)
                      ->where('end_time', '>=', $endTime);
                })->orWhere(function ($q) use ($startTime, $endTime) {
                    // Existing booking is completely within new booking
                    $q->where('date', '>=', $startTime)
                      ->where('end_time', '<=', $endTime);
                })->orWhere(function ($q) use ($startTime, $endTime) {
                    // New booking is completely within existing booking
                    $q->where('date', '<=', $startTime)
                      ->where('end_time', '>=', $endTime);
                });
            })
            ->get(['id', 'name', 'date', 'end_time']);

        // If overlapping bookings found, halt creation and show dialog
        if ($overlappingBookings->count() > 0) {
            $overlappingList = $overlappingBookings->map(function ($booking) {
                return '• ' . $booking->name . ' (' . 
                       Carbon::parse($booking->date)->format('H:i') . ' - ' . 
                       Carbon::parse($booking->end_time)->format('H:i') . ')';
            })->join("\n");

            // Show the confirmation dialog
            $this->dispatch('open-modal', id: 'overlap-confirmation');
            
            // Store overlapping details for the modal and warning display
            $this->overlappingBookings = $overlappingList;
            
            // Show warning notification
            Notification::make()
                ->warning()
                ->title('⚠️ Afspraak overlapt')
                ->body('Deze afspraak overlapt met de volgende afspraken. Bekijk de details hieronder en gebruik "Ja, Afspraak maken" om door te gaan.')
                ->persistent()
                ->send();
            
            // Halt the creation process
            $this->halt();
        }
    }
    
    public $overlappingBookings = '';
    
    public function getOverlapWarningMessage(): ?string
    {
        if (empty($this->overlappingBookings)) {
            return null;
        }
        
        return 'Deze afspraak overlapt met de volgende afspraken:' . "\n\n" . $this->overlappingBookings;
    }
    
    protected function getFormActions(): array
    {
        $actions = [];
        
        // Add warning message if overlaps detected
        if (!empty($this->overlappingBookings)) {
            $warningMessage = str_replace("\n", '<br>', htmlspecialchars($this->getOverlapWarningMessage()));
            $actions[] = Actions\Action::make('warningDisplay')
                ->label(new \Illuminate\Support\HtmlString(
                    view('components.booking-overlap-warning', [
                        'warningMessage' => $warningMessage
                    ])->render()
                ))
                ->disabled()
                ->color('warning')
                ->extraAttributes(['style' => 'width: 100%; pointer-events: none; background: transparent; border: none; padding: 0;']);
        }
        
        $actions[] = $this->getCreateFormAction();
        
        $actions[] = Actions\Action::make('confirmOverlap')
            ->label('Yes, Create Anyway')
            ->color('danger')
            ->icon('heroicon-o-exclamation-triangle')
            ->modalHeading('Booking Overlap Detected')
            ->modalDescription(fn() => 'This booking overlaps with existing bookings for the same gender:' . "\n\n" . $this->overlappingBookings . "\n\n" . 'Do you want to create this booking anyway?')
            ->modalSubmitActionLabel('Yes, Create Anyway')
            ->modalCancelActionLabel('Cancel')
            ->action(function () {
                // Set flag to bypass overlap check
                $this->overlapConfirmed = true;
                // Trigger creation again
                $this->create();
            })
            ->visible(fn() => !empty($this->overlappingBookings));
            
        $actions[] = $this->getCancelFormAction();
        
        return $actions;
    }
    
    protected function getHeaderActions(): array
    {
        return [
            // No actions in header anymore
        ];
    }

    protected function afterCreate(): void
    {
        // Load relationships for email templates
        $this->record->load(['services', 'products']);
        
        // Send creation email with proper relationships loaded
        $this->record->sendCreationEmail();
        
        // Reset the overlap flag for next time
        $this->overlapConfirmed = false;
        $this->overlappingBookings = '';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Store the auto-calculation preference for use in afterSave
        $this->shouldAutoCalculateEndTime = $data['auto_calculate_end_time'] ?? true;
        
        // Remove the helper field before saving
        unset($data['auto_calculate_end_time']);

        return $data;
    }

    protected function afterSave(): void
    {
        // Refresh record to ensure relationships are loaded
        $this->record->refresh();
        
        // Recalculate end time if auto-calculation was enabled
        if ($this->shouldAutoCalculateEndTime) {
            $this->record->recalculateEndTime();
        }
    }
}
