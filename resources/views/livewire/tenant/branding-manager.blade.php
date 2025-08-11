<div>
    <div class="mb-6">
        <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">Organization Branding</h2>
        <p class="text-gray-600 dark:text-gray-400 mt-2">Customize your organization's appearance and branding.</p>
    </div>

    @if (session()->has('message'))
        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
            {{ session('message') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
            {{ session('error') }}
        </div>
    @endif

    <form wire:submit.prevent="saveBranding" class="space-y-8">
        <!-- Organization Name -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Organization Information</h3>
                <div>
                    <label for="organization_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Organization Name
                    </label>
                    <input type="text" wire:model="organization_name" id="organization_name" 
                           class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                    @error('organization_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <!-- Logo Upload -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Logo</h3>
                
                <div class="space-y-4">
                    <!-- Current Logo Display -->
                    @if($logo || $logoPreview)
                        <div class="flex items-center space-x-4">
                            <div class="flex-shrink-0">
                                <img class="h-16 w-16 object-contain rounded-lg border border-gray-200 dark:border-gray-600 bg-white p-2" 
                                     src="{{ $logoPreview ?: Storage::url($logo) }}" 
                                     alt="Organization Logo">
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">Current Logo</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $logoPreview ? 'New logo (not saved yet)' : 'Current active logo' }}
                                </p>
                            </div>
                            @if(!$logoPreview && $logo)
                                <button type="button" wire:click="removeLogo" 
                                        class="text-red-600 hover:text-red-800 text-sm">
                                    Remove
                                </button>
                            @endif
                        </div>
                    @endif

                    <!-- Logo Upload -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Upload New Logo
                        </label>
                        <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 dark:border-gray-600 border-dashed rounded-md">
                            <div class="space-y-1 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                    <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <div class="flex text-sm text-gray-600 dark:text-gray-400">
                                    <label for="logoFile" class="relative cursor-pointer bg-white dark:bg-gray-800 rounded-md font-medium text-indigo-600 hover:text-indigo-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500">
                                        <span>Upload a file</span>
                                        <input id="logoFile" wire:model="logoFile" type="file" class="sr-only" accept="image/*">
                                    </label>
                                    <p class="pl-1">or drag and drop</p>
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">PNG, JPG, GIF up to 1MB</p>
                            </div>
                        </div>
                        @error('logoFile') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- Color Scheme -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Color Scheme</h3>
                
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <label for="primary_color" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Primary Color
                        </label>
                        <div class="mt-1 flex items-center space-x-3">
                            <input type="color" wire:model="primary_color" id="primary_color" 
                                   class="h-10 w-16 border border-gray-300 dark:border-gray-600 rounded-md cursor-pointer">
                            <input type="text" wire:model="primary_color" 
                                   class="flex-1 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        @error('primary_color') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="secondary_color" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Secondary Color
                        </label>
                        <div class="mt-1 flex items-center space-x-3">
                            <input type="color" wire:model="secondary_color" id="secondary_color" 
                                   class="h-10 w-16 border border-gray-300 dark:border-gray-600 rounded-md cursor-pointer">
                            <input type="text" wire:model="secondary_color" 
                                   class="flex-1 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        @error('secondary_color') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="mt-4 flex items-center space-x-4">
                    <button type="button" wire:click="resetColors" 
                            class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200">
                        Reset to defaults
                    </button>
                </div>
            </div>
        </div>

        <!-- Preview -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Preview</h3>
                
                <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-4" 
                     style="background-color: {{ $primary_color }}10;">
                    <div class="flex items-center space-x-4">
                        @if($logo || $logoPreview)
                            <img class="h-12 w-12 object-contain" 
                                 src="{{ $logoPreview ?: Storage::url($logo) }}" 
                                 alt="Logo Preview">
                        @else
                            <div class="h-12 w-12 rounded-lg flex items-center justify-center text-white font-bold text-lg"
                                 style="background-color: {{ $primary_color }};">
                                {{ substr($organization_name ?: 'ORG', 0, 2) }}
                            </div>
                        @endif
                        <div>
                            <h4 class="text-lg font-semibold" style="color: {{ $primary_color }};">
                                {{ $organization_name ?: 'Your Organization' }}
                            </h4>
                            <p class="text-sm" style="color: {{ $secondary_color }};">
                                This is how your branding will appear
                            </p>
                        </div>
</div>

                    <div class="mt-4 flex space-x-3">
                        <button type="button" 
                                class="px-4 py-2 rounded-md text-white font-medium"
                                style="background-color: {{ $primary_color }};">
                            Primary Button
                        </button>
                        <button type="button" 
                                class="px-4 py-2 rounded-md border font-medium"
                                style="border-color: {{ $secondary_color }}; color: {{ $secondary_color }};">
                            Secondary Button
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Save Button -->
        <div class="flex justify-end">
            <button type="submit" 
                    class="px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Save Branding
            </button>
        </div>
    </form>
</div>