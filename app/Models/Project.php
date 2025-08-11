<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;
use Illuminate\Support\Str;

class Project extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'slug',
        'status',
        'user_id',
        'tenant_id',
        'settings',
        'metadata',
        'start_date',
        'end_date',
        'budget',
        'is_public',
        'is_featured',
    ];

    protected $casts = [
        'settings' => 'array',
        'metadata' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'budget' => 'decimal:2',
        'is_public' => 'boolean',
        'is_featured' => 'boolean',
    ];

    /**
     * Get the user that owns the project
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the tenant that owns the project
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the tasks for this project
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Get the team members for this project
     */
    public function teamMembers(): HasMany
    {
        return $this->hasMany(ProjectTeamMember::class);
    }

    /**
     * Get the users through team members
     */
    public function users(): HasManyThrough
    {
        return $this->hasManyThrough(User::class, ProjectTeamMember::class);
    }

    /**
     * Get the comments for this project
     */
    public function comments(): HasMany
    {
        return $this->hasMany(ProjectComment::class);
    }

    /**
     * Check if project is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if project is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if project is on hold
     */
    public function isOnHold(): bool
    {
        return $this->status === 'on_hold';
    }

    /**
     * Check if project is overdue
     */
    public function isOverdue(): bool
    {
        return $this->end_date && $this->end_date->isPast() && !$this->isCompleted();
    }

    /**
     * Get project progress percentage
     */
    public function getProgressPercentage(): float
    {
        $totalTasks = $this->tasks()->count();
        if ($totalTasks === 0) {
            return 0;
        }

        $completedTasks = $this->tasks()->where('status', 'completed')->count();
        return round(($completedTasks / $totalTasks) * 100, 2);
    }

    /**
     * Get project duration in days
     */
    public function getDurationInDays(): ?int
    {
        if (!$this->start_date || !$this->end_date) {
            return null;
        }

        return $this->start_date->diffInDays($this->end_date);
    }

    /**
     * Get remaining days
     */
    public function getRemainingDays(): ?int
    {
        if (!$this->end_date || $this->isCompleted()) {
            return null;
        }

        return now()->diffInDays($this->end_date, false);
    }

    /**
     * Get project budget usage percentage
     */
    public function getBudgetUsagePercentage(): float
    {
        if (!$this->budget || $this->budget == 0) {
            return 0;
        }

        $totalSpent = $this->tasks()->sum('cost');
        return round(($totalSpent / $this->budget) * 100, 2);
    }

    /**
     * Get project status for display
     */
    public function getStatusDisplay(): string
    {
        return match($this->status) {
            'active' => 'Active',
            'completed' => 'Completed',
            'on_hold' => 'On Hold',
            'cancelled' => 'Cancelled',
            default => ucfirst($this->status),
        };
    }

    /**
     * Get project status color
     */
    public function getStatusColor(): string
    {
        return match($this->status) {
            'active' => 'success',
            'completed' => 'primary',
            'on_hold' => 'warning',
            'cancelled' => 'danger',
            default => 'secondary',
        };
    }

    /**
     * Get project setting
     */
    public function getSetting(string $key, $default = null)
    {
        return data_get($this->settings, $key, $default);
    }

    /**
     * Set project setting
     */
    public function setSetting(string $key, $value): void
    {
        $settings = $this->settings ?? [];
        data_set($settings, $key, $value);
        $this->update(['settings' => $settings]);
    }

    /**
     * Scope for active projects
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for completed projects
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for overdue projects
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', '!=', 'completed')
            ->where('end_date', '<', now());
    }

    /**
     * Scope for public projects
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope for featured projects
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Get project by slug
     */
    public static function findBySlug(string $slug): ?self
    {
        return static::where('slug', $slug)->first();
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Generate slug from name if not provided
        static::creating(function ($project) {
            if (empty($project->slug)) {
                $project->slug = Str::slug($project->name);
            }
        });
    }
}
