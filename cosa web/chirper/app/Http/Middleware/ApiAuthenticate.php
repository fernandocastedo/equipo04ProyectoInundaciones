<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\FloodApiClient;
use App\Services\FloodApiExceptions\ApiUnauthorizedException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ApiAuthenticate
{
    public function __construct(private readonly FloodApiClient $api)
    {
    }

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = (string) $request->session()->get('api_token', '');

        if ($token === '') {
            if (! $request->expectsJson()) {
                $request->session()->put('intended', $request->getRequestUri());
            }

            return $request->expectsJson()
                ? response()->json(['message' => 'Unauthenticated.'], 401)
                : redirect()->route('login');
        }

        if (! $request->session()->has('api_user')) {
            try {
                $user = $this->api->me($token);
                $request->session()->put('api_user', $user);
            } catch (ApiUnauthorizedException) {
                $request->session()->forget(['api_token', 'api_user']);

                if (! $request->expectsJson()) {
                    $request->session()->put('intended', $request->getRequestUri());
                }

                return $request->expectsJson()
                    ? response()->json(['message' => 'Unauthenticated.'], 401)
                    : redirect()->route('login');
            }
        }

        return $next($request);
    }
}
