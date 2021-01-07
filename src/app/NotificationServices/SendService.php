<?php

namespace App\NotificationServices;

use App\Notifications\MyNotification;
use Illuminate\Support\Facades\Notification;

class SendService
{
    public function send($sendInfo)
    {
        Notification::route('mail', $sendInfo['mail_to'])
            ->notify(new MyNotification($sendInfo['subject'], $sendInfo['message']));
    }
}
