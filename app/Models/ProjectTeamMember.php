<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectTeamMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'user_id',
        'role',
        'permissions',
        'joined_at',
        'is_active',
    ];

    protected $casts = [
        'permissions' => 'array',
        'joined_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Get the project that owns the team member
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the user that owns the team member
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if team member is active
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Check if team member has specific permission
     */
    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions ?? []);
    }

    /**
     * Get role display name
     */
    public function getRoleDisplay(): string
    {
        return match($this->role) {
            'owner' => 'Owner',
            'admin' => 'Admin',
            'member' => 'Member',
            'viewer' => 'Viewer',
            default => ucfirst($this->role),
        };
    }

    /**
     * Scope for active team members
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for team members with specific role
     */
    public function scopeWithRole($query, string $role)
    {
        return $query->where('role', $role);
    }
}
