<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Mail\EmailVerificationCustom;
use Illuminate\Support\Facades\Mail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomVerifyEmail extends VerifyEmail
{
    use Queueable;

    public function toMail($notifiable)
    {
        $verificationUrl = $this->verificationUrl($notifiable);
        
        // Create instance of the custom mail class
        $mail = new EmailVerificationCustom($verificationUrl);
        
        // Manually set the recipient and send
        return $mail->to($notifiable->email);
    }
}
