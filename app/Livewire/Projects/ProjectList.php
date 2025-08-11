<?php

namespace App\Livewire\Projects;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Project;
use Illuminate\Support\Facades\Auth;

class ProjectList extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = '';
    public $showCreateForm = false;
    
    // Form fields
    public $name = '';
    public $description = '';
    public $status = 'active';
    public $start_date = '';
    public $end_date = '';
    public $budget = '';

    protected $rules = [
        'name' => 'required|string|max:255',
        'description' => 'nullable|string|max:1000',
        'status' => 'required|in:active,completed,on_hold,cancelled',
        'start_date' => 'nullable|date',
        'end_date' => 'nullable|date|after_or_equal:start_date',
        'budget' => 'nullable|numeric|min:0',
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
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
        $this->name = '';
        $this->description = '';
        $this->status = 'active';
        $this->start_date = '';
        $this->end_date = '';
        $this->budget = '';
        $this->resetErrorBag();
    }

    public function createProject()
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'description' => $this->description,
            'slug' => \Str::slug($this->name . '-' . \Str::random(6)),
            'status' => $this->status,
            'user_id' => Auth::id(),
            'start_date' => $this->start_date ?: null,
            'end_date' => $this->end_date ?: null,
            'budget' => $this->budget ?: null,
        ];

        Project::create($data);

        $this->resetForm();
        $this->showCreateForm = false;
        
        session()->flash('message', 'Project created successfully!');
    }

    public function render()
    {
        $query = Project::where('user_id', Auth::id());

        if ($this->search) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        $projects = $query->orderBy('updated_at', 'desc')->paginate(10);

        return view('livewire.projects.project-list', [
            'projects' => $projects
        ]);
    }
}