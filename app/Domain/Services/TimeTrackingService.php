<?php

namespace App\Domain\Services;

use App\Models\TimeEntry;
use App\Models\Task;
use App\Models\User;
use App\Domain\ValueObjects\TimeDuration;
use App\Domain\ValueObjects\Money;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class TimeTrackingService
{
    /**
     * Start time tracking for a task
     */
    public function startTimeTracking(Task $task, User $user, string $description = null): TimeEntry
    {
        // Stop any existing active time entries for this user
        $this->stopAllActiveTimeEntries($user);

        return TimeEntry::create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'start_time' => now(),
            'description' => $description,
            'rate' => $user->hourly_rate ?? 0,
        ]);
    }

    /**
     * Stop time tracking for a specific task
     */
    public function stopTimeTracking(Task $task, User $user): ?TimeEntry
    {
        $timeEntry = TimeEntry::where('task_id', $task->id)
            ->where('user_id', $user->id)
            ->whereNull('end_time')
            ->first();

        if ($timeEntry) {
            $this->completeTimeEntry($timeEntry);
            return $timeEntry;
        }

        return null;
    }

    /**
     * Stop all active time entries for a user
     */
    public function stopAllActiveTimeEntries(User $user): void
    {
        TimeEntry::where('user_id', $user->id)
            ->whereNull('end_time')
            ->update([
                'end_time' => now(),
                'duration_minutes' => $this->calculateDurationFromStartTime(now()),
                'cost' => $this->calculateCostFromDuration($this->calculateDurationFromStartTime(now()), $user->hourly_rate ?? 0),
            ]);
    }

    /**
     * Complete a time entry
     */
    public function completeTimeEntry(TimeEntry $timeEntry): void
    {
        if ($timeEntry->isActive()) {
            $duration = $this->calculateDurationFromStartTime($timeEntry->start_time);
            $cost = $this->calculateCostFromDuration($duration, $timeEntry->rate);

            $timeEntry->update([
                'end_time' => now(),
                'duration_minutes' => $duration,
                'cost' => $cost,
            ]);
        }
    }

    /**
     * Get active time entry for user
     */
    public function getActiveTimeEntry(User $user): ?TimeEntry
    {
        return TimeEntry::where('user_id', $user->id)
            ->whereNull('end_time')
            ->first();
    }

    /**
     * Get time entries for task
     */
    public function getTimeEntriesForTask(Task $task): Collection
    {
        return $task->timeEntries()->with(['user'])->get();
    }

    /**
     * Get time entries for user
     */
    public function getTimeEntriesForUser(User $user, array $filters = []): Collection
    {
        $query = TimeEntry::where('user_id', $user->id);

        if (isset($filters['start_date'])) {
            $query->where('start_time', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->where('start_time', '<=', $filters['end_date']);
        }

        if (isset($filters['task_id'])) {
            $query->where('task_id', $filters['task_id']);
        }

        if (isset($filters['completed'])) {
            if ($filters['completed']) {
                $query->whereNotNull('end_time');
            } else {
                $query->whereNull('end_time');
            }
        }

        return $query->with(['task', 'user'])->get();
    }

    /**
     * Calculate total duration for task
     */
    public function calculateTaskDuration(Task $task): TimeDuration
    {
        $totalMinutes = $task->timeEntries()
            ->whereNotNull('end_time')
            ->sum('duration_minutes');

        return TimeDuration::fromMinutes($totalMinutes);
    }

    /**
     * Calculate total cost for task
     */
    public function calculateTaskCost(Task $task): Money
    {
        $totalCost = $task->timeEntries()
            ->whereNotNull('end_time')
            ->sum('cost');

        return Money::fromCents((int) ($totalCost * 100));
    }

    /**
     * Calculate total duration for user
     */
    public function calculateUserDuration(User $user, Carbon $startDate = null, Carbon $endDate = null): TimeDuration
    {
        $query = TimeEntry::where('user_id', $user->id)
            ->whereNotNull('end_time');

        if ($startDate) {
            $query->where('start_time', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('start_time', '<=', $endDate);
        }

        $totalMinutes = $query->sum('duration_minutes');

        return TimeDuration::fromMinutes($totalMinutes);
    }

    /**
     * Calculate total cost for user
     */
    public function calculateUserCost(User $user, Carbon $startDate = null, Carbon $endDate = null): Money
    {
        $query = TimeEntry::where('user_id', $user->id)
            ->whereNotNull('end_time');

        if ($startDate) {
            $query->where('start_time', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('start_time', '<=', $endDate);
        }

        $totalCost = $query->sum('cost');

        return Money::fromCents((int) ($totalCost * 100));
    }

    /**
     * Get time tracking statistics
     */
    public function getTimeTrackingStatistics(User $user = null, Task $task = null): array
    {
        $query = TimeEntry::whereNotNull('end_time');

        if ($user) {
            $query->where('user_id', $user->id);
        }

        if ($task) {
            $query->where('task_id', $task->id);
        }

        $totalEntries = $query->count();
        $totalMinutes = $query->sum('duration_minutes');
        $totalCost = $query->sum('cost');
        $averageRate = $query->avg('rate');

        $duration = TimeDuration::fromMinutes($totalMinutes);
        $cost = Money::fromCents((int) ($totalCost * 100));

        return [
            'total_entries' => $totalEntries,
            'total_duration' => $duration,
            'total_cost' => $cost,
            'average_rate' => $averageRate,
            'average_duration_per_entry' => $totalEntries > 0 ? TimeDuration::fromMinutes($totalMinutes / $totalEntries) : TimeDuration::zero(),
        ];
    }

    /**
     * Get time tracking by date range
     */
    public function getTimeTrackingByDateRange(Carbon $startDate, Carbon $endDate, User $user = null): Collection
    {
        $query = TimeEntry::whereBetween('start_time', [$startDate, $endDate])
            ->whereNotNull('end_time');

        if ($user) {
            $query->where('user_id', $user->id);
        }

        return $query->with(['task', 'user'])->get();
    }

    /**
     * Calculate duration from start time to now
     */
    private function calculateDurationFromStartTime(Carbon $startTime): int
    {
        return $startTime->diffInMinutes(now());
    }

    /**
     * Calculate cost from duration and rate
     */
    private function calculateCostFromDuration(int $durationMinutes, float $rate): float
    {
        $hours = $durationMinutes / 60;
        return round($hours * $rate, 2);
    }

    /**
     * Validate time entry
     */
    public function validateTimeEntry(TimeEntry $timeEntry): array
    {
        $errors = [];

        if ($timeEntry->start_time && $timeEntry->end_time) {
            if ($timeEntry->start_time->isAfter($timeEntry->end_time)) {
                $errors[] = 'Start time cannot be after end time';
            }

            $duration = $timeEntry->start_time->diffInMinutes($timeEntry->end_time);
            if ($duration > 1440) { // More than 24 hours
                $errors[] = 'Time entry cannot exceed 24 hours';
            }
        }

        return $errors;
    }
}
