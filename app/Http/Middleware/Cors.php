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
        // List of allowed origins (add your frontend URLs here)
        $allowedOrigins = [
            'http://localhost:5173',
            'http://127.0.0.1:5173',
            'http://127.0.0.1:8000',
            'http://127.0.0.1:8000/api-dashboard',
            'https://ketiai.com' // Add your production frontend URL
        ];

       

        // Get the request origin
        $origin = $request->headers->get('Origin');

        // Check if the origin is allowed
        if (in_array($origin, $allowedOrigins)) {
            header("Access-Control-Allow-Origin: $origin");
        }

        // Allow specific headers
        header('Access-Control-Allow-Headers: Content-Type, X-Auth-Token, Authorization, Origin');

        // Allow specific HTTP methods
        header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH,  DELETE, OPTIONS');

        // Allow credentials (if needed)
        header('Access-Control-Allow-Credentials: true');

        // Handle preflight requests
        if ($request->getMethod() === "OPTIONS") {
            return response()->json([], 204);
        }

        return $next($request);
    }
}