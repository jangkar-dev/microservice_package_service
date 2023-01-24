<?php

namespace App\Services;

use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class ResponseService
{
    static function userAuthority($user_id)
    {
        if ($user_id != Session::get('user')->id) {
            return false;
        }
        return true;
    }

    static function json($payload, $status, $data = null)
    {
        $response = [
            'message' => ucfirst($payload) . ' ' . Response::$statusTexts[$status]
        ];
        if($data !== null) {
            $response['data'] = $data;
        }
        return response()->json($response, $status);
    }
}
