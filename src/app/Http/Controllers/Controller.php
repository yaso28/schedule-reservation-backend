<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function success($data = null)
    {
        if (is_null($data)) {
            return response()->json('', 204);
        } else {
            return response()->json([
                'data' => $data,
            ], 200);
        }
    }

    public function responseWithCode($jsonResource, $statusCode = 200)
    {
        return $jsonResource->response()->setStatusCode($statusCode);
    }
}
