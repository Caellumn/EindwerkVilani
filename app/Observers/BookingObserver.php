<?php

namespace App\Observers;

use App\Models\Booking;
use App\Notifications\BookingConfirmed;
use App\Notifications\BookingCancelled;
use App\Notifications\SimpleNotifiable;
use Illuminate\Support\Facades\Notification as LaravelNotification;

class BookingObserver
{
    /**
     * Handle the Booking "created" event.
     */
    public function created(Booking $booking): void
    {
        //
    }

    /**
     * Handle the Booking "updated" event.
     */
    public function updated(Booking $booking): void
    {
        // Check if status field was changed
        if ($booking->wasChanged('status')) {
            $originalStatus = $booking->getOriginal('status');
            $newStatus = $booking->status;
            
            // If status changed from non-confirmed to confirmed, send confirmation email
            if ($originalStatus !== 'confirmed' && $newStatus === 'confirmed') {
                // Load relationships for the email
                $booking->load(['services', 'products']);
                
                // Create a simple notifiable object with the email
                $notifiable = new SimpleNotifiable($booking->email);
                
                // Send confirmation email
                LaravelNotification::send($notifiable, new BookingConfirmed($booking));
            }
            
            // If status changed from non-cancelled to cancelled, send cancellation email
            if ($originalStatus !== 'cancelled' && $newStatus === 'cancelled') {
                // Load relationships for the email
                $booking->load(['services', 'products']);
                
                // Create a simple notifiable object with the email
                $notifiable = new SimpleNotifiable($booking->email);
                
                // Send cancellation email
                LaravelNotification::send($notifiable, new BookingCancelled($booking));
            }
        }
    }

    /**
     * Handle the Booking "deleted" event.
     */
    public function deleted(Booking $booking): void
    {
        //
    }

    /**
     * Handle the Booking "restored" event.
     */
    public function restored(Booking $booking): void
    {
        //
    }

    /**
     * Handle the Booking "force deleted" event.
     */
    public function forceDeleted(Booking $booking): void
    {
        //
    }
}
