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
        $allowedOrigins = [
            'http://localhost:5173',
            'http://localhost:5173/asset-finance-loans',
            'https://laravelbackendchil.onrender.com'
        ];

        $origin = $request->header('Origin');
        
        if (in_array($origin, $allowedOrigins)) {
            header('Access-Control-Allow-Origin: ' . $origin);
            header('Access-Control-Allow-Credentials: true');
        }
        
        // Allow specific headers
        header('Access-Control-Allow-Headers: Content-Type, X-Auth-Token, Authorization, Origin');
        
        // Allow specific HTTP methods
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        
        // Handle preflight requests
        if ($request->getMethod() === "OPTIONS") {
            return response()->json();
        }
        
        return $next($request);
    }
}