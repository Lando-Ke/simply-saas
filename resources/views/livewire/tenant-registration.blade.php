<div class="min-h-screen bg-gray-50 dark:bg-gray-900 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
        <div class="flex justify-center">
            <x-application-logo class="w-20 h-20 fill-current text-gray-500" />
        </div>
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900 dark:text-white">
            Create Your Organization
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600 dark:text-gray-400">
            Set up your workspace and start managing projects
        </p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
        <div class="bg-white dark:bg-gray-800 py-8 px-4 shadow sm:rounded-lg sm:px-10">
            
            @if (session()->has('tenant_created'))
                @php $tenantData = session('tenant_created'); @endphp
                <div class="rounded-md bg-green-50 dark:bg-green-900 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-green-800 dark:text-green-200">
                                Organization Created Successfully!
                            </h3>
                            <div class="mt-2 text-sm text-green-700 dark:text-green-300">
                                <p>Your organization has been set up successfully.</p>
                                <p class="mt-1"><strong>Domain:</strong> {{ $tenantData['domain'] }}</p>
                                <p><strong>Admin Email:</strong> {{ $tenantData['admin_email'] }}</p>
                                <p class="mt-2">You can now log in to your workspace!</p>
                            </div>
                            <div class="mt-4">
                                <a href="http://{{ $tenantData['domain'] }}/login" 
                                   class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-green-700 bg-green-100 hover:bg-green-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 dark:bg-green-800 dark:text-green-200 dark:hover:bg-green-700">
                                    Go to Login
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <!-- Progress Indicator -->
                <div class="mb-8">
                    <div class="flex items-center">
                        <div class="flex items-center text-sm font-medium">
                            <span class="flex items-center justify-center w-8 h-8 rounded-full {{ $step >= 1 ? 'bg-indigo-600 text-white' : 'bg-gray-300 text-gray-500' }}">
                                1
                            </span>
                            <span class="ml-2 {{ $step >= 1 ? 'text-indigo-600' : 'text-gray-500' }}">Organization</span>
                        </div>
                        <div class="flex-1 mx-4 h-1 bg-gray-200 rounded">
                            <div class="h-1 bg-indigo-600 rounded {{ $step >= 2 ? 'w-full' : 'w-0' }} transition-all duration-300"></div>
                        </div>
                        <div class="flex items-center text-sm font-medium">
                            <span class="flex items-center justify-center w-8 h-8 rounded-full {{ $step >= 2 ? 'bg-indigo-600 text-white' : 'bg-gray-300 text-gray-500' }}">
                                2
                            </span>
                            <span class="ml-2 {{ $step >= 2 ? 'text-indigo-600' : 'text-gray-500' }}">Admin User</span>
                        </div>
                    </div>
                </div>

                @error('general')
                    <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                        {{ $message }}
                    </div>
                @enderror

                <form wire:submit.prevent="{{ $step === 1 ? 'nextStep' : 'register' }}">
                    @if ($step === 1)
                        <!-- Step 1: Organization Information -->
                        <div class="space-y-6">
                            <div>
                                <label for="organization_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Organization Name
                                </label>
                                <input type="text" wire:model.defer="organization_name" id="organization_name" 
                                       class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                       placeholder="Acme Corporation">
                                @error('organization_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label for="subdomain" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Choose Your Subdomain
                                </label>
                                <div class="mt-1 flex rounded-md shadow-sm">
                                    <input type="text" wire:model.debounce.500ms="subdomain" id="subdomain" 
                                           class="flex-1 block w-full rounded-none rounded-l-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-indigo-500 focus:border-indigo-500"
                                           placeholder="your-company">
                                    <span class="inline-flex items-center px-3 rounded-r-md border border-l-0 border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-600 text-gray-500 dark:text-gray-400 text-sm">
                                        .localhost
                                    </span>
                                </div>
                                
                                @if($isChecking)
                                    <p class="mt-1 text-sm text-gray-500">Checking availability...</p>
                                @elseif($subdomainAvailable === true)
                                    <p class="mt-1 text-sm text-green-600">✓ Available</p>
                                @elseif($subdomainAvailable === false)
                                    <p class="mt-1 text-sm text-red-600">✗ Not available</p>
                                @endif
                                
                                @error('subdomain') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    This will be your organization's URL: your-company.localhost
                                </p>
                            </div>

                            <div>
                                <label for="organization_email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Organization Email
                                </label>
                                <input type="email" wire:model.defer="organization_email" id="organization_email" 
                                       class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                       placeholder="admin@acme.com">
                                @error('organization_email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="mt-6">
                            <button type="submit" 
                                    class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Next Step
                            </button>
                        </div>
                    @endif

                    @if ($step === 2)
                        <!-- Step 2: Admin User Information -->
                        <div class="space-y-6">
                            <div>
                                <label for="admin_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Admin Name
                                </label>
                                <input type="text" wire:model.defer="admin_name" id="admin_name" 
                                       class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                       placeholder="John Doe">
                                @error('admin_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label for="admin_email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Admin Email
                                </label>
                                <input type="email" wire:model.defer="admin_email" id="admin_email" 
                                       class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                       placeholder="john@acme.com">
                                @error('admin_email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label for="admin_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Password
                                </label>
                                <input type="password" wire:model.defer="admin_password" id="admin_password" 
                                       class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                @error('admin_password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label for="admin_password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Confirm Password
                                </label>
                                <input type="password" wire:model.defer="admin_password_confirmation" id="admin_password_confirmation" 
                                       class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                @error('admin_password_confirmation') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="mt-6 flex space-x-3">
                            <button type="button" wire:click="previousStep"
                                    class="flex-1 flex justify-center py-2 px-4 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Previous
                            </button>
                            <button type="submit" 
                                    class="flex-1 flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Create Organization
                            </button>
                        </div>
                    @endif
                </form>

                <div class="mt-6">
                    <div class="relative">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-gray-300 dark:border-gray-600"></div>
                        </div>
                        <div class="relative flex justify-center text-sm">
                            <span class="px-2 bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-400">
                                Already have an account?
                            </span>
                        </div>
                    </div>
                    <div class="mt-6">
                        <a href="{{ route('login') }}" 
                           class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-indigo-600 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Sign in to existing account
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>