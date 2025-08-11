<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class PermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Please log in to access this page.');
        }

        /** @var User|null $user */
        $user = Auth::user();

        // Check if user has any of the required permissions
        if (!($user instanceof User) || !method_exists($user, 'hasAnyPermission')) {
            return $next($request);
        }

        if (!$user->hasAnyPermission($permissions)) {
            // Log the access attempt for security monitoring
            $userPermissions = [];
            if (method_exists($user, 'getAllPermissions')) {
                $userPermissions = $user->getAllPermissions()->pluck('name')->toArray();
            }

            Log::warning('Access denied for user', [
                'user_id' => $user->id,
                'email' => $user->email,
                'required_permissions' => $permissions,
                'user_permissions' => $userPermissions,
                'url' => $request->url(),
                'ip' => $request->ip(),
            ]);

            // Determine appropriate redirect based on user's highest role
            $redirectRoute = $this->getRedirectRoute($user);
            
            return redirect()->route($redirectRoute)
                ->with('error', 'You do not have permission to perform this action.');
        }

        return $next($request);
    }

    /**
     * Determine the appropriate redirect route based on user role
     */
    private function getRedirectRoute($user): string
    {
        if ($user->hasRole('super-admin') || $user->hasRole('admin')) {
            return 'dashboard';
        }
        
        if ($user->hasRole('manager')) {
            return 'projects';
        }
        
        if ($user->hasRole('team-lead')) {
            return 'projects';
        }
        
        if ($user->hasRole('client')) {
            return 'dashboard';
        }
        
        // Default for regular users
        return 'dashboard';
    }
}