<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DailyIncomeChart extends ChartWidget
{
    protected static ?int $sort = 3; // This will show second (below stats)
    
    protected static ?string $heading = 'Inkomen per dag';
    
    protected static ?string $description = 'inkomen per dag van de afgelopen 4 weken';

    protected function getData(): array
    {
        // Get last 4 weeks of data for more meaningful statistics
        $startDate = Carbon::now()->subWeeks(4)->startOfWeek();
        $endDate = Carbon::now()->endOfWeek();
        
        // Get bookings grouped by day of week, excluding Tuesday (2) and Wednesday (3)
        $bookingsByDay = Booking::whereBetween('date', [$startDate, $endDate])
            ->where('status', '!=', 'cancelled')
            ->whereNotIn(DB::raw('DAYOFWEEK(date)'), [3, 4]) // Exclude Tuesday & Wednesday
            ->get()
            ->groupBy(function ($booking) {
                return Carbon::parse($booking->date)->dayOfWeek; // 0=Sunday, 1=Monday, etc.
            });

        // Initialize income data for working days only
        $dayLabels = ['Maandag', 'Donderdag', 'Vrijdag', 'Zaterdag', 'Zondag'];
        $dayIndexes = [1, 4, 5, 6, 0]; // Carbon day indexes
        $incomeData = [];

        foreach ($dayIndexes as $dayIndex) {
            $dayBookings = $bookingsByDay->get($dayIndex, collect());
            $dayIncome = 0;
            
            foreach ($dayBookings as $booking) {
                $servicesTotal = $booking->services()->sum('price') ?? 0;
                $productsTotal = $booking->products()->sum('price') ?? 0;
                $dayIncome += $servicesTotal + $productsTotal;
            }
            
            $incomeData[] = $dayIncome;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Income (€)',
                    'data' => $incomeData,
                    'backgroundColor' => [
                        'rgba(59, 130, 246, 0.5)',   // Monday - Blue
                        'rgba(16, 185, 129, 0.5)',   // Thursday - Green
                        'rgba(245, 158, 11, 0.5)',   // Friday - Orange
                        'rgba(139, 92, 246, 0.5)',   // Saturday - Purple
                        'rgba(239, 68, 68, 0.5)',    // Sunday - Red
                    ],
                    'borderColor' => [
                        'rgba(59, 130, 246, 1)',
                        'rgba(16, 185, 129, 1)',
                        'rgba(245, 158, 11, 1)',
                        'rgba(139, 92, 246, 1)',
                        'rgba(239, 68, 68, 1)',
                    ],
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $dayLabels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
    
    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => 'function(value) { return "€" + value.toFixed(2); }',
                    ],
                ],
            ],
        ];
    }
}
