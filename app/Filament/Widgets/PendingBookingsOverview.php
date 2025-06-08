<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Booking;
use Carbon\Carbon;

class PendingBookingsOverview extends BaseWidget
{
    protected static ?int $sort = 1; // Show third (bottom)
    
    protected function getStats(): array
    {
        // Get all pending bookings
        $allPendingBookings = Booking::where('status', 'pending')
            ->where('date', '>=', now()) // Only future bookings
            ->get();
        
        // Get pending bookings in next 48 hours
        $urgentPendingBookings = Booking::where('status', 'pending')
            ->whereBetween('date', [now(), now()->addHours(48)])
            ->get();
        
        // Calculate income for pending bookings (next 48h)
        $urgentIncome = 0;
        foreach ($urgentPendingBookings as $booking) {
            $servicesTotal = $booking->services()->sum('price') ?? 0;
            $productsTotal = $booking->products()->sum('price') ?? 0;
            $urgentIncome += $servicesTotal + $productsTotal;
        }
        
        // Calculate total pending income
        $totalPendingIncome = 0;
        foreach ($allPendingBookings as $booking) {
            $servicesTotal = $booking->services()->sum('price') ?? 0;
            $productsTotal = $booking->products()->sum('price') ?? 0;
            $totalPendingIncome += $servicesTotal + $productsTotal;
        }

        return [
            Stat::make('🚨 Dringend: Volgende 48 uur', $urgentPendingBookings->count() . ' afspraken')
                ->description( $urgentPendingBookings->count() . ' afspraken moeten dringend bevestigd worden')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger') // Very obvious red color
                ->extraAttributes([
                    'class' => 'border-2 border-red-500 bg-red-50 dark:bg-red-900/20'
                ]),
                
            Stat::make('Totaal afspraken', $allPendingBookings->count() . ' afspraken')
                ->description('Alle toekomstige afspraken')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
                
            Stat::make('Potentieel inkomen (48h)', '€' . number_format($urgentIncome, 2))
                ->description('Inkomen op het spel als de afspraak niet bevestigd wordt')
                ->descriptionIcon('heroicon-m-currency-euro')
                ->color('danger'),
        ];
    }
}