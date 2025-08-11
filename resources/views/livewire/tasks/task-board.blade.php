<div>
    <div class="mb-6">
        <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">Task Board</h2>
        <p class="text-gray-600 dark:text-gray-400 mt-2">Manage tasks across your projects.</p>
    </div>

    @if (session()->has("message"))
        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
            {{ session("message") }}
        </div>
    @endif

    <!-- Project Selection -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg mb-6">
        <div class="px-4 py-5 sm:p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Select Project</h3>
                    @if($selectedProject)
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Currently viewing: <strong>{{ $selectedProject->name }}</strong>
                        </p>
                    @endif
                </div>
                @if($selectedProject)
                    <button wire:click="toggleCreateForm" 
                            class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                        Add Task
                    </button>
                @endif
            </div>
            
            @if($userProjects->count() > 0)
                <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    @foreach($userProjects as $project)
                        <button wire:click="selectProject({{ $project->id }})" 
                                class="text-left p-3 border rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700
                                       {{ $selectedProject && $selectedProject->id === $project->id ? "border-indigo-500 bg-indigo-50 dark:bg-indigo-900" : "border-gray-200 dark:border-gray-600" }}">
                            <h4 class="font-medium text-gray-900 dark:text-white">{{ $project->name }}</h4>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $project->description }}</p>
                        </button>
                    @endforeach
                </div>
            @else
                <p class="text-gray-500 dark:text-gray-400 mt-4">No active projects found. Create a project first!</p>
            @endif
        </div>
    </div>

    <!-- Create Task Form -->
    @if($showCreateForm && $selectedProject)
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg mb-6">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Create New Task</h3>
                <form wire:submit.prevent="createTask" class="space-y-4">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Task Title *</label>
                            <input type="text" wire:model="title" 
                                   class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md">
                            @error("title") <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Priority</label>
                            <select wire:model="priority" 
                                    class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md">
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                        <textarea wire:model="description" rows="3" 
                                  class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Due Date</label>
                        <input type="date" wire:model="due_date" 
                               class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md">
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button type="button" wire:click="toggleCreateForm" 
                                class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                            Create Task
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Task Board -->
    @if($selectedProject && $tasks->count() > 0)
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            @foreach(["pending", "in_progress", "completed", "cancelled"] as $status)
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
                    <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-600">
                        <h3 class="text-sm font-medium text-gray-900 dark:text-white">
                            {{ ucfirst(str_replace("_", " ", $status)) }} 
                            ({{ $tasks->get($status, collect())->count() }})
                        </h3>
                    </div>
                    <div class="p-4 space-y-3">
                        @foreach($tasks->get($status, collect()) as $task)
                            <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-3">
                                <h4 class="font-medium text-gray-900 dark:text-white text-sm">{{ $task->title }}</h4>
                                @if($task->description)
                                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">{{ Str::limit($task->description, 60) }}</p>
                                @endif
                                <div class="flex items-center justify-between mt-2">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                        {{ $task->priority === "urgent" ? "bg-red-100 text-red-800" : 
                                           ($task->priority === "high" ? "bg-orange-100 text-orange-800" : 
                                           ($task->priority === "medium" ? "bg-yellow-100 text-yellow-800" : "bg-gray-100 text-gray-800")) }}">
                                        {{ ucfirst($task->priority) }}
                                    </span>
                                    @if($task->status !== "completed" && $task->status !== "cancelled")
                                        <div class="flex space-x-1">
                                            @if($task->status === "pending")
                                                <button wire:click="updateTaskStatus({{ $task->id }}, \"in_progress\")" 
                                                        class="text-xs text-blue-600 hover:text-blue-800">Start</button>
                                            @elseif($task->status === "in_progress")
                                                <button wire:click="updateTaskStatus({{ $task->id }}, \"completed\")" 
                                                        class="text-xs text-green-600 hover:text-green-800">Complete</button>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                                @if($task->due_date)
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        Due: {{ $task->due_date->format("M j, Y") }}
                                    </p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @elseif($selectedProject)
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
            <div class="px-4 py-12 text-center">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">No tasks yet</h3>
                <p class="text-gray-600 dark:text-gray-400 mt-2">Create your first task to get started!</p>
                <button wire:click="toggleCreateForm" 
                        class="mt-4 px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                    Add First Task
                </button>
            </div>
        </div>
    @endif
</div>
