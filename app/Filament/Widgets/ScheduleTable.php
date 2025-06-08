<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use App\Models\Booking;
use Carbon\Carbon;

class ScheduleTable extends Widget
{
    protected static ?int $sort = 4;
    
    protected static string $view = 'filament.widgets.schedule-timeline';
    
    public string $selectedDate = 'today';
    
    public function getViewData(): array
    {
        $date = $this->selectedDate === 'today' ? Carbon::today() : Carbon::tomorrow();
        
        // Get bookings for selected date
        $bookings = Booking::whereDate('date', $date->format('Y-m-d'))
            ->where('status', '!=', 'cancelled')
            ->orderBy('date')
            ->get();
        
        // Separate bookings by gender
        $maleBookings = $bookings->where('gender', 'male');
        $femaleBookings = $bookings->where('gender', 'female');
        
        return [
            'selectedDate' => $this->selectedDate,
            'displayDate' => $date->format('l, F j, Y'),
            'maleBookings' => $maleBookings,
            'femaleBookings' => $femaleBookings,
            'bookingCount' => $bookings->count(),
            'debugDate' => $date->format('Y-m-d'),
        ];
    }
    
    public function switchToToday()
    {
        $this->selectedDate = 'today';
    }
    
    public function switchToTomorrow()
    {
        $this->selectedDate = 'tomorrow';
    }
}