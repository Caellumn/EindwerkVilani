<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 2; // Show second (middle)
    protected function getStats(): array
    {
        // Get the next 7 calendar days
        $startDate = Carbon::today();
        $endDate = Carbon::today()->addDays(6);
        
        // Get bookings for the next 7 days, excluding Tuesday (2) and Wednesday (3)
        $weeklyBookings = Booking::whereBetween('date', [$startDate, $endDate])
            ->where('status', '!=', 'cancelled')
            ->whereNotIn(DB::raw('DAYOFWEEK(date)'), [3, 4]) // MySQL: Sunday=1, Tuesday=3, Wednesday=4
            ->get();
        
        // Calculate total income from services and products
        $totalIncome = 0;
        foreach ($weeklyBookings as $booking) {
            $servicesTotal = $booking->services()->sum('price') ?? 0;
            $productsTotal = $booking->products()->sum('price') ?? 0;
            $totalIncome += $servicesTotal + $productsTotal;
        }
        
        // Count working days (excluding Tuesday/Wednesday) in the next 7 days
        $workingDays = collect(range(0, 6))
            ->map(fn($day) => $startDate->copy()->addDays($day))
            ->filter(fn($date) => !in_array($date->dayOfWeek, [2, 3])) // Carbon: Tuesday=2, Wednesday=3
            ->count();
        
        return [
            Stat::make('Totaal inkomen (volgende 7 dagen)', '€' . number_format($totalIncome, 2)),

                
            Stat::make('Werkdagen', $workingDays . ' van de 7 dagen')
                ->description('Salon open dagen deze week')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('primary'),
                
            Stat::make('Afspraken', $weeklyBookings->count())
                ->description('Bevestigde afspraken van de komende week')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('info'),
        ];
    }
}
