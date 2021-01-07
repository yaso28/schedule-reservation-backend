<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Contracts\Support\Renderable;

class MyMailMessage extends MailMessage implements Renderable
{
    protected $myMessage;

    public function __construct()
    {
        $senderAddress = config('mail.from.address');
        $senderName = __('mail.sender_name');
        $this->from($senderAddress, $senderName);
        $this->bcc($senderAddress, $senderName);
        $this->markdown('mails.my_mail');
    }

    public function message($message)
    {
        $this->myMessage = $message;
        return $this;
    }

    public function toArray()
    {
        return [
            'myMessage' => $this->myMessage,
        ];
    }
}
