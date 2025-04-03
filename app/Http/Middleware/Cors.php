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
        // List of allowed origins
        $allowedOrigins = [
            'http://localhost:5173',
            'http://127.0.0.1:5173',
            'http://127.0.0.1:8000',
            'http://127.0.0.1:8000/api-dashboard',
            'https://laravelbackendchil.onrender.com',
            'https://ketiai.com'
        ];

        // Get the request origin
        $origin = $request->headers->get('Origin');

        // Handle preflight requests
        if ($request->isMethod('OPTIONS')) {
            $response = response()->json([], 204);
        } else {
            $response = $next($request);
        }

        // Add headers if origin is allowed
        if (in_array($origin, $allowedOrigins)) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, X-Auth-Token, Authorization, Origin, X-Requested-With, X-CSRF-TOKEN, X-CSRFToken');
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
            $response->headers->set('Access-Control-Expose-Headers', 'X-CSRF-TOKEN');
        }

        return $response;
    }
}