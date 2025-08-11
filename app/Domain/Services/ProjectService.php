<?php

namespace App\Domain\Services;

use App\Models\Project;
use App\Models\User;
use App\Models\ProjectTeamMember;
use App\Domain\ValueObjects\Money;
use App\Domain\ValueObjects\ProjectStatus;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ProjectService
{
    /**
     * Create a new project
     */
    public function createProject(array $data, User $user): Project
    {
        $status = new ProjectStatus($data['status'] ?? ProjectStatus::ACTIVE);

        $project = Project::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'status' => $status->getValue(),
            'user_id' => $user->id,
            'tenant_id' => $data['tenant_id'] ?? null,
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'budget' => $data['budget'] ?? null,
            'is_public' => $data['is_public'] ?? false,
            'is_featured' => $data['is_featured'] ?? false,
            'settings' => $data['settings'] ?? [],
            'metadata' => $data['metadata'] ?? [],
        ]);

        // Add creator as owner
        $this->addTeamMember($project, $user, 'owner');

        return $project;
    }

    /**
     * Update project
     */
    public function updateProject(Project $project, array $data): Project
    {
        // Validate status transition if status is being updated
        if (isset($data['status'])) {
            $currentStatus = new ProjectStatus($project->status);
            $newStatus = new ProjectStatus($data['status']);
            
            if (!$currentStatus->canTransitionTo($newStatus)) {
                throw new \InvalidArgumentException("Invalid status transition from {$project->status} to {$data['status']}");
            }
        }

        $project->update($data);
        return $project->fresh();
    }

    /**
     * Add team member to project
     */
    public function addTeamMember(Project $project, User $user, string $role = 'member'): ProjectTeamMember
    {
        return ProjectTeamMember::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'role' => $role,
            'permissions' => $this->getDefaultPermissions($role),
            'joined_at' => now(),
            'is_active' => true,
        ]);
    }

    /**
     * Remove team member from project
     */
    public function removeTeamMember(Project $project, User $user): bool
    {
        return ProjectTeamMember::where('project_id', $project->id)
            ->where('user_id', $user->id)
            ->delete();
    }

    /**
     * Update team member role
     */
    public function updateTeamMemberRole(Project $project, User $user, string $role): bool
    {
        $teamMember = ProjectTeamMember::where('project_id', $project->id)
            ->where('user_id', $user->id)
            ->first();

        if (!$teamMember) {
            return false;
        }

        $teamMember->update([
            'role' => $role,
            'permissions' => $this->getDefaultPermissions($role),
        ]);

        return true;
    }

    /**
     * Get project statistics
     */
    public function getProjectStatistics(Project $project): array
    {
        $totalTasks = $project->tasks()->count();
        $completedTasks = $project->tasks()->where('status', 'completed')->count();
        $overdueTasks = $project->tasks()->overdue()->count();
        $totalHours = $project->tasks()->sum('actual_hours');
        $totalCost = $project->tasks()->sum('cost');

        return [
            'total_tasks' => $totalTasks,
            'completed_tasks' => $completedTasks,
            'overdue_tasks' => $overdueTasks,
            'completion_rate' => $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100, 2) : 0,
            'total_hours' => $totalHours,
            'total_cost' => $totalCost,
            'progress_percentage' => $project->getProgressPercentage(),
            'budget_usage_percentage' => $project->getBudgetUsagePercentage(),
            'remaining_days' => $project->getRemainingDays(),
        ];
    }

    /**
     * Get projects for user
     */
    public function getProjectsForUser(User $user, array $filters = []): Collection
    {
        $query = Project::where('user_id', $user->id);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['is_public'])) {
            $query->where('is_public', $filters['is_public']);
        }

        if (isset($filters['search'])) {
            $query->where('name', 'like', '%' . $filters['search'] . '%');
        }

        return $query->with(['user', 'tasks'])->get();
    }

    /**
     * Get public projects
     */
    public function getPublicProjects(array $filters = []): Collection
    {
        $query = Project::public()->active();

        if (isset($filters['featured'])) {
            $query->featured();
        }

        if (isset($filters['search'])) {
            $query->where('name', 'like', '%' . $filters['search'] . '%');
        }

        return $query->with(['user'])->get();
    }

    /**
     * Check if user can access project
     */
    public function canUserAccessProject(User $user, Project $project): bool
    {
        // Project owner can always access
        if ($project->user_id === $user->id) {
            return true;
        }

        // Check if user is team member
        $teamMember = ProjectTeamMember::where('project_id', $project->id)
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->first();

        return $teamMember !== null;
    }

    /**
     * Check if user can edit project
     */
    public function canUserEditProject(User $user, Project $project): bool
    {
        // Project owner can always edit
        if ($project->user_id === $user->id) {
            return true;
        }

        // Check if user is admin or owner
        $teamMember = ProjectTeamMember::where('project_id', $project->id)
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->whereIn('role', ['owner', 'admin'])
            ->first();

        return $teamMember !== null;
    }

    /**
     * Get default permissions for role
     */
    private function getDefaultPermissions(string $role): array
    {
        return match($role) {
            'owner' => ['view', 'edit', 'delete', 'manage_team', 'manage_tasks'],
            'admin' => ['view', 'edit', 'manage_team', 'manage_tasks'],
            'member' => ['view', 'edit', 'manage_tasks'],
            'viewer' => ['view'],
            default => ['view'],
        };
    }

    /**
     * Calculate project timeline
     */
    public function calculateProjectTimeline(Project $project): array
    {
        $startDate = $project->start_date;
        $endDate = $project->end_date;

        if (!$startDate || !$endDate) {
            return [
                'duration_days' => null,
                'elapsed_days' => null,
                'remaining_days' => null,
                'progress_percentage' => null,
            ];
        }

        $durationDays = $startDate->diffInDays($endDate);
        $elapsedDays = $startDate->diffInDays(now());
        $remainingDays = now()->diffInDays($endDate, false);

        $progressPercentage = $durationDays > 0 ? min(100, max(0, ($elapsedDays / $durationDays) * 100)) : 0;

        return [
            'duration_days' => $durationDays,
            'elapsed_days' => $elapsedDays,
            'remaining_days' => $remainingDays,
            'progress_percentage' => round($progressPercentage, 2),
        ];
    }

    /**
     * Get project budget analysis
     */
    public function getProjectBudgetAnalysis(Project $project): array
    {
        $budget = Money::fromCents((int) (($project->budget ?? 0) * 100));
        $totalSpent = Money::fromCents((int) ($project->tasks()->sum('cost') * 100));
        
        // Handle over-budget scenario
        $isOverBudget = $totalSpent->getAmount() > $budget->getAmount();
        $remainingBudget = $isOverBudget ? Money::zero() : $budget->subtract($totalSpent);
        $budgetUsagePercentage = $budget->isZero() ? 0 : ($totalSpent->getAmountInCents() / $budget->getAmountInCents()) * 100;

        return [
            'budget' => $budget,
            'total_spent' => $totalSpent,
            'remaining_budget' => $remainingBudget,
            'budget_usage_percentage' => round($budgetUsagePercentage, 2),
            'is_over_budget' => $isOverBudget,
        ];
    }
}
