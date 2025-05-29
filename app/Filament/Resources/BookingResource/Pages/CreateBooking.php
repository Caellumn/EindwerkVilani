<?php

namespace App\Filament\Resources\BookingResource\Pages;

use App\Filament\Resources\BookingResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Models\Booking;
use Carbon\Carbon;
use Filament\Notifications\Notification;

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
                ->title('⚠️ Booking Overlap Detected')
                ->body('This booking conflicts with existing bookings for the same gender. Review the details below and use "Yes, Create Anyway" to proceed.')
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
        
        return 'This booking overlaps with existing bookings for the same gender:' . "\n\n" . $this->overlappingBookings;
    }
    
    protected function getFormActions(): array
    {
        $actions = [];
        
        // Add warning message if overlaps detected
        if (!empty($this->overlappingBookings)) {
            $warningMessage = str_replace("\n", '<br>', htmlspecialchars($this->getOverlapWarningMessage()));
            $actions[] = Actions\Action::make('warningDisplay')
                ->label(new \Illuminate\Support\HtmlString('
                    <div class="bg-warning-50 dark:bg-warning-900/20 border border-warning-200 dark:border-warning-700 rounded-lg p-4 mb-4 w-full">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-warning-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-warning-800 dark:text-warning-200">Booking Overlap Detected</h3>
                                <div class="mt-2 text-sm text-warning-700 dark:text-warning-300">
                                    ' . $warningMessage . '
                                </div>
                            </div>
                        </div>
                    </div>
                '))
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
