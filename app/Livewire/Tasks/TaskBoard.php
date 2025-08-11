<?php

namespace App\Livewire\Tasks;

use Livewire\Component;
use App\Models\Task;
use App\Models\Project;
use Illuminate\Support\Facades\Auth;

class TaskBoard extends Component
{
    public $selectedProject = null;
    public $showCreateForm = false;
    
    // Form fields
    public $title = '';
    public $description = '';
    public $priority = 'medium';
    public $due_date = '';

    protected $rules = [
        'title' => 'required|string|max:255',
        'description' => 'nullable|string|max:1000',
        'priority' => 'required|in:low,medium,high,urgent',
        'due_date' => 'nullable|date',
    ];

    public function mount($projectId = null)
    {
        if ($projectId) {
            $this->selectedProject = Project::where('user_id', Auth::id())
                ->where('id', $projectId)
                ->first();
        }
    }

    public function selectProject($projectId)
    {
        $this->selectedProject = Project::where('user_id', Auth::id())
            ->where('id', $projectId)
            ->first();
    }

    public function toggleCreateForm()
    {
        $this->showCreateForm = !$this->showCreateForm;
        if (!$this->showCreateForm) {
            $this->resetForm();
        }
    }

    public function resetForm()
    {
        $this->title = '';
        $this->description = '';
        $this->priority = 'medium';
        $this->due_date = '';
        $this->resetErrorBag();
    }

    public function createTask()
    {
        $this->validate();

        if (!$this->selectedProject) {
            session()->flash('error', 'Please select a project first.');
            return;
        }

        Task::create([
            'title' => $this->title,
            'description' => $this->description,
            'priority' => $this->priority,
            'status' => 'pending',
            'project_id' => $this->selectedProject->id,
            'created_by' => Auth::id(),
            'due_date' => $this->due_date ?: null,
        ]);

        $this->resetForm();
        $this->showCreateForm = false;
        
        session()->flash('message', 'Task created successfully!');
    }

    public function updateTaskStatus($taskId, $status)
    {
        $task = Task::whereHas('project', function($query) {
            $query->where('user_id', Auth::id());
        })->find($taskId);

        if ($task) {
            $task->update(['status' => $status]);
            session()->flash('message', 'Task status updated!');
        }
    }

    public function render()
    {
        $userProjects = Project::where('user_id', Auth::id())
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $tasks = collect();
        if ($this->selectedProject) {
            $tasks = Task::where('project_id', $this->selectedProject->id)
                ->orderBy('created_at', 'desc')
                ->get()
                ->groupBy('status');
        }

        return view('livewire.tasks.task-board', [
            'userProjects' => $userProjects,
            'tasks' => $tasks,
        ]);
    }
}