<?php

namespace App\Http\Requests;

class SendRequest extends RequestBase
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'mail_to' => "bail|required|email",
            'subject' => "bail|required",
            'message' => "bail|required",
        ];
    }

    public function values()
    {
        return $this->only([
            'mail_to',
            'subject',
            'message',
        ]);
    }
}
