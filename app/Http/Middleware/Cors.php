<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Cors
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Dynamic allowed origins
        $allowedOrigins = [
            'https://ketiai.com',
            'https://www.ketiai.com',
            'https://laravelbackendchil.onrender.com',
            'http://localhost:5173',
            'http://127.0.0.1:8000'
        ];

        $origin = $request->headers->get('Origin');

        // Handle preflight requests
        if ($request->isMethod('OPTIONS')) {
            $response = response()->json([], 204);
        } else {
            $response = $next($request);
        }

        // Apply CORS headers
        if (in_array($origin, $allowedOrigins)) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-CSRF-TOKEN, x-amz-acl');
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
            $response->headers->set('Access-Control-Max-Age', '86400');
        }

        return $response;
    }
}