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
        // Dynamic allowed origins (consider loading from .env)
        $allowedOrigins = [
            'http://localhost:5173',
            'http://127.0.0.1:8000',
            'http://127.0.0.1:5173',
            'http://localhost:5173/',
            'https://laravelbackendchil.onrender.com',
            'https://ketiai.com',
            'https://voiceflow.com', // Add VoiceFlow domains
            'https://*.voiceflow.com' // Wildcard for all subdomains
        ];

        $origin = $request->headers->get('Origin');

        // Handle preflight requests
        if ($request->isMethod('OPTIONS')) {
            return $this->addCorsHeaders(response()->json([], 204), $origin, $allowedOrigins);
        }

        $response = $next($request);

        // Apply CORS headers to actual responses
        return $this->addCorsHeaders($response, $origin, $allowedOrigins);
    }

    protected function addCorsHeaders(Response $response, ?string $origin, array $allowedOrigins): Response
    {
        if (in_array($origin, $allowedOrigins)) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-CSRF-TOKEN');
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
            $response->headers->set('Access-Control-Max-Age', '86400'); // 24 hours
        }

        return $response;
    }
}