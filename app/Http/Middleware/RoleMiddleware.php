<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Please log in to access this page.');
        }

        /** @var User|null $user */
        $user = Auth::user();

        // Check if user has any of the required roles
        if (!($user instanceof User) || !method_exists($user, 'hasAnyRole')) {
            return $next($request);
        }

        if (!$user->hasAnyRole($roles)) {
            // Determine appropriate redirect based on user's role
            if ($user->hasRole('client')) {
                return redirect()->route('dashboard')
                    ->with('error', 'You do not have permission to access this page.');
            }
            
            if ($user->hasRole('user') || $user->hasRole('team-lead')) {
                return redirect()->route('projects')
                    ->with('error', 'You do not have permission to access this page.');
            }
            
            if ($user->hasRole('manager') || $user->hasRole('admin')) {
                return redirect()->route('dashboard')
                    ->with('error', 'You do not have permission to access this page.');
            }

            // Default redirect for unknown roles
            return redirect()->route('dashboard')
                ->with('error', 'Access denied. You do not have the required role.');
        }

        return $next($request);
    }
}