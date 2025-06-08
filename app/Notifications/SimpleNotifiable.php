<?php

namespace App\Notifications;

use Illuminate\Notifications\Notifiable;

class SimpleNotifiable
{
    use Notifiable;

    public $email;

    public function __construct($email)
    {
        $this->email = $email;
    }

    /**
     * Route notifications for the mail channel.
     *
     * @return string
     */
    public function routeNotificationForMail()
    {
        return $this->email;
    }
} 