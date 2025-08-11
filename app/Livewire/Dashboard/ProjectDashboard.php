<?php

namespace App\Livewire\Dashboard;

use Livewire\Component;
use App\Models\Project;
use App\Models\Task;
use App\Models\TimeEntry;
use Illuminate\Support\Facades\Auth;

class ProjectDashboard extends Component
{
    public $stats = [];
    public $recentProjects = [];
    public $recentTasks = [];
    public $activeTimeEntry = null;

    public function mount()
    {
        $this->loadDashboardData();
    }

    public function loadDashboardData()
    {
        $user = Auth::user();
        
        // Calculate stats
        $this->stats = [
            'total_projects' => Project::where('user_id', $user->id)->count(),
            'active_projects' => Project::where('user_id', $user->id)->where('status', 'active')->count(),
            'pending_tasks' => Task::whereHas('project', function($query) use ($user) {
                $query->where('user_id', $user->id);
            })->where('status', 'pending')->count(),
            'completed_tasks' => Task::whereHas('project', function($query) use ($user) {
                $query->where('user_id', $user->id);
            })->where('status', 'completed')->count(),
        ];

        // Load recent projects
        $this->recentProjects = Project::where('user_id', $user->id)
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get();

        // Load recent tasks
        $this->recentTasks = Task::whereHas('project', function($query) use ($user) {
            $query->where('user_id', $user->id);
        })
        ->orderBy('updated_at', 'desc')
        ->limit(10)
        ->with('project')
        ->get();

        // Check for active time entry
        $this->activeTimeEntry = TimeEntry::where('user_id', $user->id)
            ->whereNull('end_time')
            ->with('task.project')
            ->first();
    }

    public function startTimeTracking($taskId)
    {
        $user = Auth::user();
        
        // Stop any existing active time entry
        TimeEntry::where('user_id', $user->id)
            ->whereNull('end_time')
            ->update(['end_time' => now()]);

        // Start new time entry
        TimeEntry::create([
            'user_id' => $user->id,
            'task_id' => $taskId,
            'start_time' => now(),
        ]);

        $this->loadDashboardData();
    }

    public function stopTimeTracking()
    {
        if ($this->activeTimeEntry) {
            $this->activeTimeEntry->update(['end_time' => now()]);
            $this->loadDashboardData();
        }
    }

    public function render()
    {
        return view('livewire.dashboard.project-dashboard');
    }
}