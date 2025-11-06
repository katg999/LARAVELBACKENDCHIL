<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    protected function redirectTo($request)
    {
        if (! $request->expectsJson()) {
            // Check if the request is for school, health-facility, or doctor routes
            $path = $request->path();
            if (str_starts_with($path, 'school') || 
                str_starts_with($path, 'health-facility') || 
                str_starts_with($path, 'doctor')) {
                return url('/');
            }
            
            return route('login');
        }
    }
}
