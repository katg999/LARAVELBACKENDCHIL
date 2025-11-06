<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LogRequests
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
        // Skip logging for static asset requests
        if ($this->isStaticAsset($request)) {
            return $next($request);
        }

        $startTime = microtime(true);

        // Log the incoming request
        Log::info('REQUEST START', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'headers' => $request->headers->all(),
            'user_id' => auth()->id(),
            'user_email' => auth()->user() ? auth()->user()->email : null,
            'session_id' => session()->getId(),
            'timestamp' => now()->toISOString(),
        ]);

        $response = $next($request);

        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000, 2); // Convert to milliseconds

        // Log the response
        Log::info('REQUEST END', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'status_code' => $response->getStatusCode(),
            'duration_ms' => $duration,
            'response_size' => strlen($response->getContent()),
            'timestamp' => now()->toISOString(),
        ]);

        return $response;
    }

    /**
     * Check if the request is for a static asset
     */
    private function isStaticAsset(Request $request): bool
    {
        $path = $request->path();
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        // Static file extensions
        $staticExtensions = [
            'css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'ico',
            'woff', 'woff2', 'ttf', 'eot', 'pdf', 'txt', 'xml', 'json'
        ];

        // Check file extension
        if (in_array($extension, $staticExtensions)) {
            return true;
        }

        // Check common static asset paths
        $staticPaths = [
            'css/', 'js/', 'images/', 'img/', 'assets/', 'fonts/',
            'storage/', 'favicon.ico'
        ];

        foreach ($staticPaths as $staticPath) {
            if (str_contains($path, $staticPath)) {
                return true;
            }
        }

        return false;
    }
}