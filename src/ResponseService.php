<?php

namespace App\Services;

use Illuminate\Http\Resources\MissingValue;
use Symfony\Component\HttpFoundation\Response;

class ResponseService
{
    static function userAuthority($user_id)
    {
        if ($user_id != config('user')->id) {
            return false;
        }
        return true;
    }

    static function json($payload, $status)
    {
        return response()->json([
            'message' => ucfirst($payload) . ' ' . Response::$statusTexts[$status]
        ], $status);
    }
}
