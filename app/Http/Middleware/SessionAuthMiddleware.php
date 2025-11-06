<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SessionAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @param  string  $userType
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, $userType = null)
    {
        // Check if user is authenticated via session
        $authenticatedUser = $request->session()->get('authenticated_user');

        if (!$authenticatedUser) {
            // Redirect to homepage for unauthenticated access
            return redirect('/')->with('error', 'Please log in to access this page.');
        }

        // If specific user type is required, check it
        if ($userType && $authenticatedUser['type'] !== $userType) {
            return redirect('/')->with('error', 'Unauthorized access.');
        }

        // Add the authenticated user to the request for easy access
        $request->merge(['current_user' => $authenticatedUser]);

        return $next($request);
    }
}