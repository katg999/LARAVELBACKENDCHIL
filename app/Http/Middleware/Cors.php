<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Cors
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next)
    {
        // Allow requests from specific origin (replace with your frontend URL)
        header('Access-Control-Allow-Origin: http://localhost:5173');

        // Allow specific headers
        header('Access-Control-Allow-Headers: Content-Type, X-Auth-Token, Authorization, Origin');

        // Allow specific HTTP methods
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');

        // Allow credentials (if needed)
        header('Access-Control-Allow-Credentials: true');

        // Handle preflight requests
        if ($request->getMethod() === "OPTIONS") {
            return response()->json();
        }

        return $next($request);
    }
}