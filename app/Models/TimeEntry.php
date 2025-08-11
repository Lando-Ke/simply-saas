<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class TimeEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'user_id',
        'start_time',
        'end_time',
        'duration_minutes',
        'description',
        'rate',
        'cost',
        'metadata',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'duration_minutes' => 'integer',
        'rate' => 'decimal:2',
        'cost' => 'decimal:2',
        'metadata' => 'array',
    ];

    /**
     * Get the task that owns the time entry
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Get the user that owns the time entry
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if time entry is active (running)
     */
    public function isActive(): bool
    {
        return $this->start_time && !$this->end_time;
    }

    /**
     * Check if time entry is completed
     */
    public function isCompleted(): bool
    {
        return $this->start_time && $this->end_time;
    }

    /**
     * Get duration in hours
     */
    public function getDurationInHours(): float
    {
        return $this->duration_minutes / 60;
    }

    /**
     * Get duration for display
     */
    public function getDurationDisplay(): string
    {
        $hours = floor($this->duration_minutes / 60);
        $minutes = $this->duration_minutes % 60;
        
        if ($hours > 0) {
            return sprintf('%dh %dm', $hours, $minutes);
        }
        
        return sprintf('%dm', $minutes);
    }

    /**
     * Get cost for display
     */
    public function getDisplayCost(): string
    {
        return '$' . number_format($this->cost ?? 0, 2);
    }

    /**
     * Get rate for display
     */
    public function getDisplayRate(): string
    {
        return '$' . number_format($this->rate ?? 0, 2) . '/hr';
    }

    /**
     * Calculate duration from start and end times
     */
    public function calculateDuration(): int
    {
        if (!$this->start_time || !$this->end_time) {
            return 0;
        }

        return $this->start_time->diffInMinutes($this->end_time);
    }

    /**
     * Calculate cost based on duration and rate
     */
    public function calculateCost(): float
    {
        $hours = $this->getDurationInHours();
        return $hours * ($this->rate ?? 0);
    }

    /**
     * Stop the time entry
     */
    public function stop(): void
    {
        if ($this->isActive()) {
            $this->update([
                'end_time' => now(),
                'duration_minutes' => $this->calculateDuration(),
                'cost' => $this->calculateCost(),
            ]);
        }
    }

    /**
     * Scope for active time entries
     */
    public function scopeActive($query)
    {
        return $query->whereNotNull('start_time')->whereNull('end_time');
    }

    /**
     * Scope for completed time entries
     */
    public function scopeCompleted($query)
    {
        return $query->whereNotNull('start_time')->whereNotNull('end_time');
    }

    /**
     * Scope for time entries by user
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for time entries by task
     */
    public function scopeByTask($query, int $taskId)
    {
        return $query->where('task_id', $taskId);
    }
}
