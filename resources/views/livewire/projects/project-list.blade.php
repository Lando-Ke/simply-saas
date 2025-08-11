<div>
    <h1 class="text-2xl font-semibold text-gray-900 dark:text-white mb-6">Projects</h1>
    
    @if($projects->count() > 0)
        <div class="space-y-4">
            @foreach($projects as $project)
                <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow">
                    <h3 class="text-lg font-medium">{{ $project->name }}</h3>
                    <p class="text-gray-600 dark:text-gray-400">{{ $project->description }}</p>
                    <span class="inline-block px-2 py-1 text-xs bg-green-100 text-green-800 rounded">
                        {{ $project->status }}
                    </span>
                </div>
            @endforeach
        </div>
        {{ $projects->links() }}
    @else
        <p class="text-gray-500">No projects found. Create your first project!</p>
    @endif
</div>
