<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class OpeningTime extends Model
{
    protected $table = 'openingtimes';
    
    protected $fillable = [
        'day',
        'status',
        'open',
        'close',
    ];

    protected $casts = [
        'open' => 'string',
        'close' => 'string',
    ];

    /**
     * Get opening times for a specific day
     */
    public static function getOpeningTimeForDay(string $day): ?self
    {
        return self::where('day', strtolower($day))->first();
    }

    /**
     * Check if the salon is open on a specific day
     */
    public static function isOpenOnDay(string $day): bool
    {
        $openingTime = self::where('day', strtolower($day))->first();
        
        // Return true only if record exists and status is 'open'
        return $openingTime && $openingTime->status === 'open';
    }

    /**
     * Get all opening days
     */
    public static function getOpenDays(): array
    {
        return self::where('status', 'open')->pluck('day')->toArray();
    }

    /**
     * Check if a given time is within opening hours for a specific day
     */
    public static function isWithinOpeningHours(string $day, string $time): bool
    {
        $openingTime = self::getOpeningTimeForDay($day);
        
        if (!$openingTime || $openingTime->status !== 'open') {
            return false; // Salon is closed on this day
        }

        try {
            $checkTime = Carbon::createFromFormat('H:i', $time);
            
            // Extract HH:MM from the stored time format (HH:MM:SS)
            $openTimeStr = substr($openingTime->open, 0, 5); // Get HH:MM
            $closeTimeStr = substr($openingTime->close, 0, 5); // Get HH:MM
            
            $openTime = Carbon::createFromFormat('H:i', $openTimeStr);
            $closeTime = Carbon::createFromFormat('H:i', $closeTimeStr);

            return $checkTime->between($openTime, $closeTime);
        } catch (\Exception $e) {
            return false; // If time parsing fails, assume closed
        }
    }

    /**
     * Get formatted opening hours string for display
     */
    public function getFormattedHours(): string
    {
        // If status is gesloten, return "Gesloten"
        if ($this->status === 'gesloten') {
            return 'Gesloten';
        }
        
        // Check if open/close times are null even if status is open
        if (!$this->open || !$this->close) {
            return 'Gesloten';
        }
        
        // Simple string manipulation to get HH:MM format
        $openTime = substr($this->open, 0, 5); // Get first 5 characters (HH:MM)
        $closeTime = substr($this->close, 0, 5); // Get first 5 characters (HH:MM)
        
        return "{$openTime} - {$closeTime}";
    }

    /**
     * Get opening hours for all days in a week format
     */
    public static function getWeeklySchedule(): array
    {
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $schedule = [];

        foreach ($days as $day) {
            $openingTime = self::getOpeningTimeForDay($day);
            
            if ($openingTime) {
                // Check the status field
                if ($openingTime->status === 'gesloten') {
                    $schedule[$day] = [
                        'status' => 'gesloten',
                        'open' => null,
                        'close' => null,
                        'formatted' => 'Gesloten',
                        'is_open' => false
                    ];
                } else {
                    $schedule[$day] = [
                        'status' => 'open',
                        'open' => $openingTime->open,
                        'close' => $openingTime->close,
                        'formatted' => $openingTime->getFormattedHours(),
                        'is_open' => true
                    ];
                }
            } else {
                $schedule[$day] = [
                    'status' => 'gesloten',
                    'open' => null,
                    'close' => null,
                    'formatted' => 'Gesloten',
                    'is_open' => false
                ];
            }
        }

        return $schedule;
    }
} 