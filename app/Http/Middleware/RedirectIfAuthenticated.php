<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        if (Auth::guard($guard)->check()) {
            // Allow authenticated users to access invitation registration flows
            if ($request->has(['invitation_token', 'invitation_type'])) {
                return $next($request);
            }
            
            return redirect('/home');
        }

        return $next($request);
    }
}
