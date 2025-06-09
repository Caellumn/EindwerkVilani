<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Carbon\Carbon;
use App\Notifications\BookingCreated;
use App\Notifications\BookingConfirmed;
use App\Notifications\SimpleNotifiable;
use Illuminate\Support\Facades\Notification as LaravelNotification;

class Booking extends Model
{
    //has uuid
    use HasUuids;

    protected $fillable = ['date', 'name', 'email', 'telephone', 'gender', 'remarks', 'status', 'user_id', 'time', 'end_time'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function services()
    {
        return $this->belongsToMany(Service::class)
                    ->using(BookingService::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class)
                    ->using(BookingProduct::class);
    }

    /**
     * Send the appropriate creation email based on booking status
     * This method should be called AFTER all relationships are set up
     * 
     * @return void
     */
    public function sendCreationEmail()
    {
        // Load relationships to ensure they're available for email templates
        $this->load(['services', 'products']);
        
        // Create a simple notifiable object with the email
        $notifiable = new SimpleNotifiable($this->email);
        
        // Send appropriate email based on status
        if ($this->status === 'pending') {
            LaravelNotification::send($notifiable, new BookingCreated($this));
        } elseif ($this->status === 'confirmed') {
            LaravelNotification::send($notifiable, new BookingConfirmed($this));
        }
    }

    /**
     * Recalculate and update the booking end time based on all attached services
     * 
     * @return void
     */
    public function recalculateEndTime()
    {
        // Calculate total time of all services in minutes and cast to integer
        $totalServiceTime = (int) $this->services()->sum('time');
        
        if ($totalServiceTime > 0) {
            // Update end_time = start_time + total_service_time
            $this->end_time = Carbon::parse($this->date)->addMinutes($totalServiceTime);
        } else {
            // If no services, set end_time same as start_time
            $this->end_time = Carbon::parse($this->date);
        }
        
        $this->save();
    }
}
