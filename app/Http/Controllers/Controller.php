<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Response;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected function textResponse($content): \Illuminate\Http\Response
    {
        // Make sure we're sending a text.
        $headers = [
            'Content-type' => 'text/plain',
            'Content-Length' => strlen($content)
        ];

        // Serve that text file!
        return Response::make($content, 200, $headers);
    }
}
