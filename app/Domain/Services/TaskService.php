<?php

namespace App\Domain\Services;

use App\Models\Task;
use App\Models\User;
use App\Models\Project;
use App\Models\TimeEntry;
use App\Domain\ValueObjects\TaskStatus;
use App\Domain\ValueObjects\TaskPriority;
use App\Domain\ValueObjects\TimeDuration;
use App\Domain\ValueObjects\Money;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class TaskService
{
    /**
     * Create a new task
     */
    public function createTask(array $data, User $user): Task
    {
        $status = new TaskStatus($data['status'] ?? TaskStatus::PENDING);
        $priority = new TaskPriority($data['priority'] ?? TaskPriority::MEDIUM);

        return Task::create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'status' => $status->getValue(),
            'priority' => $priority->getValue(),
            'project_id' => $data['project_id'],
            'assigned_to' => $data['assigned_to'] ?? null,
            'created_by' => $user->id,
            'due_date' => $data['due_date'] ?? null,
            'estimated_hours' => $data['estimated_hours'] ?? null,
            'cost' => $data['cost'] ?? null,
            'tags' => $data['tags'] ?? [],
            'metadata' => $data['metadata'] ?? [],
        ]);
    }

    /**
     * Update task
     */
    public function updateTask(Task $task, array $data): Task
    {
        // Validate status transition if status is being updated
        if (isset($data['status'])) {
            $currentStatus = new TaskStatus($task->status);
            $newStatus = new TaskStatus($data['status']);
            
            if (!$currentStatus->canTransitionTo($newStatus)) {
                throw new \InvalidArgumentException("Invalid status transition from {$task->status} to {$data['status']}");
            }
        }

        $task->update($data);
        return $task->fresh();
    }

    /**
     * Assign task to user
     */
    public function assignTask(Task $task, User $user): bool
    {
        return $task->update(['assigned_to' => $user->id]);
    }

    /**
     * Mark task as completed
     */
    public function completeTask(Task $task): bool
    {
        $task->markAsCompleted();
        return true;
    }

    /**
     * Mark task as in progress
     */
    public function startTask(Task $task): bool
    {
        $task->markAsInProgress();
        return true;
    }

    /**
     * Add tag to task
     */
    public function addTagToTask(Task $task, string $tag): void
    {
        $task->addTag($tag);
    }

    /**
     * Remove tag from task
     */
    public function removeTagFromTask(Task $task, string $tag): void
    {
        $task->removeTag($tag);
    }

    /**
     * Get tasks for user
     */
    public function getTasksForUser(User $user, array $filters = []): Collection
    {
        $query = Task::where('assigned_to', $user->id);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (isset($filters['project_id'])) {
            $query->where('project_id', $filters['project_id']);
        }

        if (isset($filters['overdue'])) {
            $query->overdue();
        }

        if (isset($filters['search'])) {
            $query->where('title', 'like', '%' . $filters['search'] . '%');
        }

        return $query->with(['project', 'assignedUser', 'createdBy'])->get();
    }

    /**
     * Get tasks for project
     */
    public function getTasksForProject(Project $project, array $filters = []): Collection
    {
        $query = $project->tasks();

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (isset($filters['assigned_to'])) {
            $query->where('assigned_to', $filters['assigned_to']);
        }

        if (isset($filters['overdue'])) {
            $query->overdue();
        }

        if (isset($filters['search'])) {
            $query->where('title', 'like', '%' . $filters['search'] . '%');
        }

        return $query->with(['assignedUser', 'createdBy'])->get();
    }

    /**
     * Get task statistics
     */
    public function getTaskStatistics(Project $project = null): array
    {
        $query = $project ? $project->tasks() : Task::query();

        $totalTasks = $query->count();
        $completedTasks = $query->clone()->where('status', 'completed')->count();
        $pendingTasks = $query->clone()->where('status', 'pending')->count();
        $inProgressTasks = $query->clone()->where('status', 'in_progress')->count();
        $overdueTasks = $query->clone()->overdue()->count();
        $highPriorityTasks = $query->clone()->highPriority()->count();

        return [
            'total_tasks' => $totalTasks,
            'completed_tasks' => $completedTasks,
            'pending_tasks' => $pendingTasks,
            'in_progress_tasks' => $inProgressTasks,
            'overdue_tasks' => $overdueTasks,
            'high_priority_tasks' => $highPriorityTasks,
            'completion_rate' => $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100, 2) : 0,
        ];
    }

    /**
     * Get overdue tasks
     */
    public function getOverdueTasks(User $user = null): Collection
    {
        $query = Task::overdue();

        if ($user) {
            $query->where('assigned_to', $user->id);
        }

        return $query->with(['project', 'assignedUser'])->get();
    }

    /**
     * Get high priority tasks
     */
    public function getHighPriorityTasks(User $user = null): Collection
    {
        $query = Task::highPriority();

        if ($user) {
            $query->where('assigned_to', $user->id);
        }

        return $query->with(['project', 'assignedUser'])->get();
    }

    /**
     * Start time tracking for task
     */
    public function startTimeTracking(Task $task, User $user, string $description = null): TimeEntry
    {
        // Stop any existing active time entries for this user
        TimeEntry::where('user_id', $user->id)
            ->whereNull('end_time')
            ->update(['end_time' => now()]);

        return TimeEntry::create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'start_time' => now(),
            'description' => $description,
            'rate' => $user->hourly_rate ?? 0,
        ]);
    }

    /**
     * Stop time tracking for task
     */
    public function stopTimeTracking(Task $task, User $user): ?TimeEntry
    {
        $timeEntry = TimeEntry::where('task_id', $task->id)
            ->where('user_id', $user->id)
            ->whereNull('end_time')
            ->first();

        if ($timeEntry) {
            $timeEntry->stop();
            return $timeEntry;
        }

        return null;
    }

    /**
     * Get time entries for task
     */
    public function getTimeEntriesForTask(Task $task): Collection
    {
        return $task->timeEntries()->with(['user'])->get();
    }

    /**
     * Calculate task cost
     */
    public function calculateTaskCost(Task $task): Money
    {
        $timeEntries = $task->timeEntries()->completed()->get();
        $totalCost = $timeEntries->sum('cost');
        return Money::fromCents((int) ($totalCost * 100));
    }

    /**
     * Get task timeline
     */
    public function getTaskTimeline(Task $task): array
    {
        $createdAt = $task->created_at;
        $dueDate = $task->due_date;
        $completedAt = $task->completed_at;

        $timeline = [
            'created_at' => $createdAt,
            'due_date' => $dueDate,
            'completed_at' => $completedAt,
            'is_overdue' => $task->isOverdue(),
            'days_until_due' => $dueDate ? now()->diffInDays($dueDate, false) : null,
        ];

        if ($completedAt) {
            $timeline['duration_days'] = $createdAt->diffInDays($completedAt);
        }

        return $timeline;
    }

    /**
     * Get task tags
     */
    public function getTaskTags(Project $project = null): array
    {
        $query = $project ? $project->tasks() : Task::query();
        
        $tasks = $query->whereNotNull('tags')->get();
        $tags = [];

        foreach ($tasks as $task) {
            foreach ($task->tags ?? [] as $tag) {
                if (!in_array($tag, $tags)) {
                    $tags[] = $tag;
                }
            }
        }

        return $tags;
    }

    /**
     * Get tasks by tag
     */
    public function getTasksByTag(string $tag, Project $project = null): Collection
    {
        $query = $project ? $project->tasks() : Task::query();
        
        return $query->whereJsonContains('tags', $tag)->get();
    }
}
