<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\Access\Response;

class AccessControlServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Define gates for common access patterns
        Gate::define('access-admin-panel', function ($user) {
            return $user->hasPermissionTo('access admin panel')
                ? Response::allow()
                : Response::deny('You do not have permission to access the admin panel.');
        });

        Gate::define('manage-users', function ($user) {
            return $user->hasAnyPermission(['view users', 'create users', 'edit users', 'delete users'])
                ? Response::allow()
                : Response::deny('You do not have permission to manage users.');
        });

        Gate::define('manage-projects', function ($user) {
            return $user->hasAnyPermission(['view projects', 'create projects', 'edit projects', 'delete projects'])
                ? Response::allow()
                : Response::deny('You do not have permission to manage projects.');
        });

        Gate::define('manage-billing', function ($user) {
            return $user->hasAnyPermission(['manage billing', 'view financial data', 'view subscriptions'])
                ? Response::allow()
                : Response::deny('You do not have permission to manage billing.');
        });

        Gate::define('view-analytics', function ($user) {
            return $user->hasPermissionTo('view analytics')
                ? Response::allow()
                : Response::deny('You do not have permission to view analytics.');
        });

        Gate::define('manage-settings', function ($user) {
            return $user->hasAnyPermission(['manage settings', 'manage branding'])
                ? Response::allow()
                : Response::deny('You do not have permission to manage settings.');
        });

        // Project-specific gates
        Gate::define('view-project', function ($user, $project) {
            // Super admin and admin can view all projects
            if ($user->hasRole(['super-admin', 'admin'])) {
                return Response::allow();
            }

            // Check if user has general project view permission
            if (!$user->hasPermissionTo('view projects')) {
                return Response::deny('You do not have permission to view projects.');
            }

            // Check if user is assigned to this specific project
            if ($project->users()->where('user_id', $user->id)->exists()) {
                return Response::allow();
            }

            // Check if project is public
            if ($project->is_public) {
                return Response::allow();
            }

            return Response::deny('You do not have access to this project.');
        });

        Gate::define('edit-project', function ($user, $project) {
            // Super admin and admin can edit all projects
            if ($user->hasRole(['super-admin', 'admin'])) {
                return Response::allow();
            }

            // Check if user has general project edit permission
            if (!$user->hasPermissionTo('edit projects')) {
                return Response::deny('You do not have permission to edit projects.');
            }

            // Check if user is project owner or team member with edit rights
            $teamMember = $project->teamMembers()->where('user_id', $user->id)->first();
            if ($teamMember && in_array($teamMember->role, ['owner', 'admin'])) {
                return Response::allow();
            }

            return Response::deny('You do not have permission to edit this project.');
        });

        // Task-specific gates
        Gate::define('view-task', function ($user, $task) {
            // Super admin and admin can view all tasks
            if ($user->hasRole(['super-admin', 'admin'])) {
                return Response::allow();
            }

            // Check if user has general task view permission
            if (!$user->hasPermissionTo('view tasks')) {
                return Response::deny('You do not have permission to view tasks.');
            }

            // Check if user is assigned to the project this task belongs to
            if ($task->project && $task->project->users()->where('user_id', $user->id)->exists()) {
                return Response::allow();
            }

            // Check if user is assigned to this specific task
            if ($task->assigned_to === $user->id) {
                return Response::allow();
            }

            return Response::deny('You do not have access to this task.');
        });

        Gate::define('edit-task', function ($user, $task) {
            // Super admin and admin can edit all tasks
            if ($user->hasRole(['super-admin', 'admin'])) {
                return Response::allow();
            }

            // Check if user has general task edit permission
            if (!$user->hasPermissionTo('edit tasks')) {
                return Response::deny('You do not have permission to edit tasks.');
            }

            // Check if user is assigned to this task or is a team lead/manager on the project
            if ($task->assigned_to === $user->id) {
                return Response::allow();
            }

            if ($task->project) {
                $teamMember = $task->project->teamMembers()->where('user_id', $user->id)->first();
                if ($teamMember && in_array($teamMember->role, ['owner', 'admin', 'member'])) {
                    return Response::allow();
                }
            }

            return Response::deny('You do not have permission to edit this task.');
        });
    }
}