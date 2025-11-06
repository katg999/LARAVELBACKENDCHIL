<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles  One or more role slugs (comma-separated in route definition)
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // Only log if session is available (not in testing context)
        if ($request->hasSession()) {
            \Log::info('RoleMiddleware check', [
                'url' => $request->fullUrl(),
                'session_id' => $request->session()->getId(),
                'auth_check' => Auth::check(),
                'user_id' => Auth::id(),
                'required_roles' => $roles
            ]);
        }

        if (!Auth::check()) {
            if ($request->hasSession()) {
                \Log::warning('RoleMiddleware: User not authenticated', [
                    'url' => $request->fullUrl(),
                    'session_id' => $request->session()->getId()
                ]);
            }
            return redirect()->route('login')->with('error', 'Please login to access this page.');
        }

        $user = Auth::user();
        
        if ($request->hasSession()) {
            \Log::info('RoleMiddleware: User authenticated', [
                'user_id' => $user->id,
                'email' => $user->email,
                'user_roles' => $user->roles->pluck('slug')->toArray()
            ]);
        }

        // Check if user has any of the specified roles
        $hasRole = false;
        foreach ($roles as $role) {
            if ($user->hasRole($role)) {
                $hasRole = true;
                break;
            }
        }

        if (!$hasRole) {
            if ($request->hasSession()) {
                \Log::warning('RoleMiddleware: User lacks required role', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'user_roles' => $user->roles->pluck('slug')->toArray(),
                    'required_roles' => $roles
                ]);
            }
            // Redirect to home page with error message instead of logging out
            return redirect('/')->with('error', 'You do not have permission to access this page.');
        }

        return $next($request);
    }
}
