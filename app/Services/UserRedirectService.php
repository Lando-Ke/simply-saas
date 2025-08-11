<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class UserRedirectService
{
    /**
     * Get the appropriate dashboard route for a user based on their role
     */
    public static function getDashboardRoute(User $user = null): string
    {
        $user = $user ?: Auth::user();
        
        if (!$user) {
            return 'login';
        }

        // Super admin and admin go to main dashboard
        if ($user->hasRole(['super-admin', 'admin'])) {
            return 'dashboard';
        }

        // Managers go to projects overview
        if ($user->hasRole('manager')) {
            return 'projects';
        }

        // Team leads go to projects
        if ($user->hasRole('team-lead')) {
            return 'projects';
        }

        // Clients go to their dashboard view
        if ($user->hasRole('client')) {
            return 'dashboard';
        }

        // Regular users go to dashboard
        return 'dashboard';
    }

    /**
     * Get available navigation items for a user based on their permissions
     */
    public static function getAvailableNavigation(User $user = null): array
    {
        $user = $user ?: Auth::user();
        
        if (!$user) {
            return [];
        }

        $navigation = [];

        // Dashboard - available to all authenticated users
        if ($user->hasPermissionTo('view dashboard')) {
            $navigation['dashboard'] = [
                'route' => 'dashboard',
                'label' => 'Dashboard',
                'icon' => 'heroicon-o-home',
            ];
        }

        // Projects
        if ($user->hasPermissionTo('view projects')) {
            $navigation['projects'] = [
                'route' => 'projects',
                'label' => 'Projects',
                'icon' => 'heroicon-o-folder',
            ];
        }

        // Tasks
        if ($user->hasPermissionTo('view tasks')) {
            $navigation['tasks'] = [
                'route' => 'tasks',
                'label' => 'Tasks',
                'icon' => 'heroicon-o-clipboard-document-list',
            ];
        }

        // Billing - only for users with billing permissions
        if ($user->hasAnyPermission(['view subscriptions', 'manage billing'])) {
            $navigation['billing'] = [
                'route' => 'billing',
                'label' => 'Billing',
                'icon' => 'heroicon-o-credit-card',
            ];
        }

        // Branding - only for users with branding management permissions
        if ($user->hasPermissionTo('manage branding')) {
            $navigation['branding'] = [
                'route' => 'branding',
                'label' => 'Branding',
                'icon' => 'heroicon-o-paint-brush',
            ];
        }

        return $navigation;
    }

    /**
     * Check if user can access a specific route
     */
    public static function canAccessRoute(string $routeName, User $user = null): bool
    {
        $user = $user ?: Auth::user();
        
        if (!$user) {
            return false;
        }

        return match ($routeName) {
            'dashboard' => $user->hasPermissionTo('view dashboard'),
            'projects' => $user->hasPermissionTo('view projects'),
            'tasks' => $user->hasPermissionTo('view tasks'),
            'billing' => $user->hasAnyPermission(['view subscriptions', 'manage billing']),
            'branding' => $user->hasPermissionTo('manage branding'),
            'profile' => true, // Profile is accessible to all authenticated users
            default => false,
        };
    }

    /**
     * Get the first accessible route for a user (fallback redirect)
     */
    public static function getFirstAccessibleRoute(User $user = null): string
    {
        $user = $user ?: Auth::user();
        
        if (!$user) {
            return 'login';
        }

        $routes = ['dashboard', 'projects', 'tasks', 'profile'];
        
        foreach ($routes as $route) {
            if (self::canAccessRoute($route, $user)) {
                return $route;
            }
        }

        // If no routes are accessible, something is wrong with permissions
        // Return profile as absolute fallback
        return 'profile';
    }
}

