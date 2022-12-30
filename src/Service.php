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
        $FIRST_URL = explode('.', $API)[0];
        if ($FIRST_URL == '127' || $FIRST_URL == '103') {
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
            'Microservice-Token' => $this->token
        ]);
    }
    public function returnRequest($response)
    {
        $response = $response->object();
        return collect($response->data ?? $response->message ?? []);
    }

    public function get($service, $request = [])
    {
        $response = $this->getRequest()->get($this->baseUrl . '/' . $service, $request);
        return $this->returnRequest($response);
    }

    public function post($service, $request = [])
    {
        $response = $this->getRequest()->post($this->baseUrl . '/' . $service, $request);
        return $this->returnRequest($response);
    }
}
