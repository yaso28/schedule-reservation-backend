<?php

namespace App\Http\Responses;

use Illuminate\Contracts\Support\Responsable;
use App\Exceptions\MyException;

class FailedLoginResponse implements Responsable
{
    public function toResponse($request)
    {
        throw new MyException(__('auth.failed'), 422);
    }
}
