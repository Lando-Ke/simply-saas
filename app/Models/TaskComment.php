<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaskComment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'task_id',
        'user_id',
        'content',
        'type',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * Get the task that owns the comment
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Get the user that owns the comment
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if comment is a system comment
     */
    public function isSystemComment(): bool
    {
        return $this->type === 'system';
    }

    /**
     * Check if comment is a user comment
     */
    public function isUserComment(): bool
    {
        return $this->type === 'user';
    }

    /**
     * Get comment type display name
     */
    public function getTypeDisplay(): string
    {
        return match($this->type) {
            'user' => 'User Comment',
            'system' => 'System Comment',
            default => ucfirst($this->type),
        };
    }

    /**
     * Scope for user comments
     */
    public function scopeUserComments($query)
    {
        return $query->where('type', 'user');
    }

    /**
     * Scope for system comments
     */
    public function scopeSystemComments($query)
    {
        return $query->where('type', 'system');
    }
}
