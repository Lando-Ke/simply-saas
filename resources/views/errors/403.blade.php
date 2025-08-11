<x-app-layout>
    <div class="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-900 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8 text-center">
            <div>
                <div class="mx-auto h-24 w-24 text-red-500">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
                <h2 class="mt-6 text-3xl font-extrabold text-gray-900 dark:text-white">
                    Access Denied
                </h2>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    You don't have permission to access this resource.
                </p>
            </div>
            
            <div class="mt-8 space-y-4">
                <p class="text-gray-500 dark:text-gray-400">
                    If you believe this is an error, please contact your administrator.
                </p>
                
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="{{ route('dashboard') }}" 
                       class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Go to Dashboard
                    </a>
                    
                    <button onclick="window.history.back()" 
                            class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Go Back
                    </button>
                </div>
            </div>

            <!-- Role Information -->
            @auth
                <div class="mt-8 p-4 bg-gray-100 dark:bg-gray-800 rounded-lg">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Your current role: <span class="font-semibold">{{ auth()->user()->getPrimaryRole() ?? 'No role assigned' }}</span>
                    </p>
                    @if(auth()->user()->roles->count() > 1)
                        <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                            Additional roles: {{ auth()->user()->roles->skip(1)->pluck('name')->join(', ') }}
                        </p>
                    @endif
                </div>
            @endauth
        </div>
    </div>
</x-app-layout>

