<?php

namespace App\Exceptions;

use Exception;

class MyException extends Exception
{
    protected $myMessage;
    protected $myStatus;

    public function __construct($message = null, $status = 499)
    {
        $this->myMessage = $message;
        $this->myStatus = $status;
    }

    public function report()
    {
        //
    }

    public function render($request)
    {
        return response()->json([
            'message' => 'Custom exception.',
            'custom_message' => $this->myMessage,
        ], $this->myStatus);
    }

    public function getCustomMessage()
    {
        return $this->myMessage;
    }

    public function getStatusCode()
    {
        return $this->myStatus;
    }
}
