<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated via Sanctum
        if (!$request->user()) {
            return response()->json([
                'success' => false,
                'message' => 'Token expired or invalid. Please log in again.',
                'error' => 'UNAUTHORIZED'
            ], 401);
        }

        // Check if token is expired (if expiration is set)
        $token = $request->user()->currentAccessToken();
        if ($token && $token->expires_at && $token->expires_at->isPast()) {
            // Delete expired token
            $token->delete();
            
            return response()->json([
                'success' => false,
                'message' => 'Token has expired. Please log in again.',
                'error' => 'TOKEN_EXPIRED'
            ], 401);
        }

        return $next($request);
    }
}
