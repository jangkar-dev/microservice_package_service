<?php

namespace App\Services;

use App\Http\Resources\AuthResource;
use Illuminate\Support\Facades\Http;

class Service
{
    protected $baseUrl;
    protected $token;
    public function __construct($type)
    {
        $SSL = "https://";
        $TYPE = strtoupper($type);
        $API = env($TYPE.'_SERVICE_URL');
        $TOKEN = env($TYPE.'_SERVICE_TOKEN');
        if (explode('.', $API)[0] == '127') {
            $SSL = "http://";
        }
        $this->baseUrl = $SSL . $API;
        $this->token = $TOKEN;
    }
    public function getRequest()
    {
        return Http::withHeaders([
            'Authenticated' => json_encode([
                'user' => config('user'),
            ]),
            'Microservice_Token' => $this->token
        ]);
    }
    public function returnRequest($response)
    {
        return collect($response->object()->data);
    }
    public function get($service, $request = [])
    {
        $response = $this->getRequest()->get($this->baseUrl . '/' . $service, $request);
        return $this->returnRequest($response);
    }
}
