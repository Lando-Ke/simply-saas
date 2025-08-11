<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class PermissionGate extends Component
{
    public $permission;
    public $role;
    public $any;
    public $fallback;

    /**
     * Create a new component instance.
     */
    public function __construct(
        string $permission = null, 
        string $role = null, 
        bool $any = false,
        string $fallback = null
    ) {
        $this->permission = $permission;
        $this->role = $role;
        $this->any = $any;
        $this->fallback = $fallback;
    }

    /**
     * Determine if the user has access
     */
    public function hasAccess(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();
        
        if (!$user) {
            return false;
        }

        // Check role if specified
        if ($this->role) {
            $roles = explode(',', $this->role);
            $roles = array_map('trim', $roles);
            
            if ($this->any) {
                if (!method_exists($user, 'hasAnyRole') || !$user->hasAnyRole($roles)) {
                    return false;
                }
            } else {
                foreach ($roles as $role) {
                    if (!method_exists($user, 'hasRole') || !$user->hasRole($role)) {
                        return false;
                    }
                }
            }
        }

        // Check permission if specified
        if ($this->permission) {
            $permissions = explode(',', $this->permission);
            $permissions = array_map('trim', $permissions);
            
            if ($this->any) {
                if (!method_exists($user, 'hasAnyPermission') || !$user->hasAnyPermission($permissions)) {
                    return false;
                }
            } else {
                foreach ($permissions as $permission) {
                    if (!method_exists($user, 'hasPermissionTo') || !$user->hasPermissionTo($permission)) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.permission-gate');
    }
}