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
        $authenticated = $request->header('Authenticated');
        $fetchParsed = json_decode($authenticated);
        $token = $request->header('microservice-token') ?? null;
        $requestInfo = [
            'host' => $requestHost,
            'ip' => $request->getClientIp(),
            'url' => $request->getRequestUri(),
            'agent' => $request->header('User-Agent'),
        ];
        Log::debug($requestInfo);
        Log::debug('Try to Access Service'.env('ALLOWED_DOMAINS'));
        $allowedHosts = explode(',', env('ALLOWED_DOMAINS'));
        if (isset($fetchParsed->user)) {
        } else {
            return response()->json('This host is not allowed', Response::HTTP_UNAUTHORIZED);
        }
        if (!app()->runningUnitTests()) {
            if (env('APP_ENV') == 'Production') {
                if (
                    !in_array($requestHost, $allowedHosts, false)
                    || $authenticated == ''
                    || $authenticated == null
                    || $fetchParsed->user == null
                    || $fetchParsed->user == ''
                    || $fetchParsed->user->id == null
                    || $fetchParsed->user->id == ''
                    || $token != env('APP_SERVICE_TOKEN')
                ) {
                    return response()->json('This host is not allowed', Response::HTTP_UNAUTHORIZED);
                }
            }
        }
        Log::debug('Access Service Accepted');
        Config::set('user', $fetchParsed->user);
        return $next($request);
    }
}
