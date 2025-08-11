<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Filament\Models\Contracts\HasTenants;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Model;
use Filament\Panel;

class User extends Authenticatable implements HasTenants, FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'hourly_rate',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'hourly_rate' => 'decimal:2',
        ];
    }

    /**
     * Get user's primary role
     *
     * @return string|null
     */
    public function getPrimaryRole(): ?string
    {
        return $this->roles->first()?->name;
    }

    /**
     * Check if user is a super admin
     *
     * @return bool
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super-admin');
    }

    /**
     * Check if user is an application admin
     *
     * @return bool
     */
    public function isAppAdmin(): bool
    {
        return $this->hasRole('app-admin') || $this->isSuperAdmin();
    }

    /**
     * Check if user is a tenant admin
     *
     * @return bool
     */
    public function isTenantAdmin(): bool
    {
        return $this->hasRole('tenant-admin') || $this->isSuperAdmin();
    }

    /**
     * Check if user is an admin (legacy method for backward compatibility)
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin') || $this->isAppAdmin();
    }

    /**
     * Get user's permissions as array
     *
     * @return array
     */
    public function getPermissionsArray(): array
    {
        return $this->getAllPermissions()->pluck('name')->toArray();
    }

    /**
     * Check if user has any of the specified roles
     *
     * @param array $roles
     * @return bool
     */
    public function hasAnyRole($roles): bool
    {
        if (is_string($roles)) {
            $roles = [$roles];
        }

        return $this->roles()->whereIn('name', $roles)->exists();
    }

    /**
     * Check if user has any of the specified permissions
     *
     * @param array $permissions
     * @return bool
     */
    public function hasAnyPermission($permissions): bool
    {
        if (is_string($permissions)) {
            $permissions = [$permissions];
        }

        foreach ($permissions as $permission) {
            if ($this->hasPermissionTo($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get user's role hierarchy level (for comparison)
     *
     * @return int
     */
    public function getRoleLevel(): int
    {
        if ($this->hasRole('super-admin')) {
            return 100;
        }
        
        if ($this->hasRole('app-admin')) {
            return 90;
        }
        
        if ($this->hasRole('tenant-admin')) {
            return 85;
        }
        
        if ($this->hasRole('admin')) {
            return 80;
        }
        
        if ($this->hasRole('manager')) {
            return 60;
        }
        
        if ($this->hasRole('team-lead')) {
            return 40;
        }
        
        if ($this->hasRole('user')) {
            return 20;
        }
        
        if ($this->hasRole('client')) {
            return 10;
        }
        
        return 0;
    }

    /**
     * Check if user can manage another user (based on role hierarchy)
     *
     * @param User $user
     * @return bool
     */
    public function canManageUser(User $user): bool
    {
        // Super admin can manage everyone
        if ($this->hasRole('super-admin')) {
            return true;
        }
        
        // Users cannot manage users with higher or equal role levels
        return $this->getRoleLevel() > $user->getRoleLevel();
    }

    /**
     * Get the tenants that this user belongs to
     */
    public function getTenants(Panel $panel): \Illuminate\Support\Collection
    {
        return $this->belongsToMany(Tenant::class, 'tenant_users')
            ->withPivot(['role', 'joined_at'])
            ->withTimestamps()
            ->get();
    }

    /**
     * Check if user can access a specific tenant
     */
    public function canAccessTenant(Model $tenant): bool
    {
        // Debug logging
        \Log::info('canAccessTenant called', [
            'user_id' => $this->id,
            'user_roles' => $this->getRoleNames()->toArray(),
            'tenant_id' => $tenant->id,
            'has_super_admin' => $this->hasRole('super-admin'),
            'has_app_admin' => $this->hasRole('app-admin'),
        ]);

        // Super admin and app admin can access all tenants
        if ($this->hasRole('super-admin') || $this->hasRole('app-admin')) {
            \Log::info('canAccessTenant: returning true for super/app admin');
            return true;
        }

        // Check if user is a member of this tenant
        $isMember = $this->tenants()->where('tenants.id', $tenant->id)->exists();
        \Log::info('canAccessTenant: tenant membership check', ['is_member' => $isMember]);
        
        return $isMember;
    }

    /**
     * Get the tenants that this user belongs to for a specific panel
     */
    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class, 'tenant_users')
            ->withPivot(['role', 'joined_at'])
            ->withTimestamps();
    }

    /**
     * Check if user can access a specific Filament panel
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // Only allow super-admins, app-admins, and tenant-admins to access admin panel
        return $this->hasRole(['super-admin', 'app-admin', 'tenant-admin']);
    }
}
