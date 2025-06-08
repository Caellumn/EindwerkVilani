<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Booking;
use Carbon\Carbon;

class BookingCancelled extends Notification
{
    use Queueable;

    public $booking;

    /**
     * Create a new notification instance.
     */
    public function __construct(Booking $booking)
    {
        $this->booking = $booking;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Uw afspraak bij Kapsalon Vilani is geannuleerd')
            ->view('emails.booking-cancelled', [
                'customerName' => $this->booking->name,
                'bookingDate' => Carbon::parse($this->booking->date)->format('l j F Y'),
                'bookingTime' => Carbon::parse($this->booking->date)->format('H:i'),
                'endTime' => Carbon::parse($this->booking->end_time)->format('H:i'),
                'services' => $this->booking->services,
                'products' => $this->booking->products,
                'booking' => $this->booking
            ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
