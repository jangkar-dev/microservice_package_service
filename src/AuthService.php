<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AuthService
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $requestHost = $request->header('host');
        $authenticated = json_decode($request->header('Authenticated'), true);
        $token = $request->header('microservice-token') ?? null;

        // Log request information for debugging purposes
        Log::debug([
            'host' => $requestHost,
            'ip' => $request->getClientIp(),
            'url' => $request->getRequestUri(),
            'agent' => $request->header('User-Agent'),
        ]);

        // Check if the host is allowed to access the service
        $allowedHosts = explode(',', env('ALLOWED_DOMAINS'));
        if (!in_array($requestHost, $allowedHosts, false)) {
            return response()->json('This host is not allowed', Response::HTTP_UNAUTHORIZED);
        }

        // Check if the request is authenticated and the token is valid
        if (app()->runningUnitTests() || env('APP_ENV') != 'Production') {
            return $next($request);
        }

        if (empty($authenticated) || empty($authenticated['user']) || $token != env('APP_SERVICE_TOKEN')) {
            return response()->json('This host is not allowed', Response::HTTP_UNAUTHORIZED);
        }

        // Set the authenticated user in the config
        Config::set('user', $authenticated['user']);

        // Access to the service is granted
        Log::debug('Access Service Accepted');

        return $next($request);
    }
}
