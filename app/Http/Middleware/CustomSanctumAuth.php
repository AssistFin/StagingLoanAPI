<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;

class CustomSanctumAuth
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
        // Get headers manually
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? null;

        if ($authHeader) {
            // Remove 'Bearer ' prefix if present
            $token = str_replace('Bearer ', '', $authHeader);

            // Find the token in the database
            $accessToken = PersonalAccessToken::findToken($token);

            if ($accessToken) {
                // Authenticate the user
                Auth::loginUsingId($accessToken->tokenable_id);
                return $next($request);
            }
        }

        return response()->json(['message' => 'Unauthorized'], 401);
    }
}
