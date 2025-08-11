<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FilamentAdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Only allow super-admins, app-admins, and tenant-admins to access Filament
        if (!$user->hasRole(['super-admin', 'app-admin', 'tenant-admin'])) {
            // For debugging
            \Log::info('FilamentAdminMiddleware: Access denied for user', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'user_roles' => $user->getRoleNames()->toArray(),
                'required_roles' => ['super-admin', 'app-admin', 'tenant-admin']
            ]);
            abort(403, 'You do not have permission to access the admin panel.');
        }

        return $next($request);
    }
}
