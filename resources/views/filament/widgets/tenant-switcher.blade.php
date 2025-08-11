<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-building-office-2 class="h-5 w-5" />
                Tenant Management
            </div>
        </x-slot>

        <div class="space-y-4">
            <!-- Current Tenant Display -->
            @if($this->getCurrentTenant())
                <div class="flex items-center justify-between p-3 bg-primary-50 dark:bg-primary-900/20 rounded-lg border border-primary-200 dark:border-primary-800">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-primary-500 rounded-full flex items-center justify-center text-white font-bold text-sm">
                            {{ substr($this->getCurrentTenant()->getName(), 0, 1) }}
                        </div>
                        <div>
                            <p class="font-medium text-primary-900 dark:text-primary-100">
                                {{ $this->getCurrentTenant()->getName() }}
                            </p>
                            <p class="text-sm text-primary-700 dark:text-primary-300">
                                Current Active Tenant
                            </p>
                        </div>
                    </div>
                    <x-filament::button 
                        wire:click="clearTenant" 
                        size="sm" 
                        color="danger" 
                        outlined
                    >
                        Clear
                    </x-filament::button>
                </div>
            @else
                <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        <x-heroicon-o-information-circle class="h-4 w-4 inline mr-1" />
                        No tenant selected. Select a tenant below to manage its data.
                    </p>
                </div>
            @endif

            <!-- Tenant List -->
            <div class="space-y-2">
                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">Available Tenants</h4>
                
                @forelse($this->getTenants() as $tenant)
                    <div class="flex items-center justify-between p-3 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-primary-300 dark:hover:border-primary-600 transition-colors">
                        <div class="flex items-center gap-3">
                            @if($tenant->getSetting('branding.logo'))
                                <img src="{{ Storage::url($tenant->getSetting('branding.logo')) }}" 
                                     alt="{{ $tenant->getName() }}" 
                                     class="w-8 h-8 rounded-full object-cover">
                            @else
                                <div class="w-8 h-8 rounded-full flex items-center justify-center text-white font-bold text-sm"
                                     style="background-color: {{ $tenant->getSetting('branding.primary_color', '#4F46E5') }};">
                                    {{ substr($tenant->getName(), 0, 1) }}
                                </div>
                            @endif
                            
                            <div class="flex-1">
                                <p class="font-medium text-gray-900 dark:text-gray-100">
                                    {{ $tenant->getName() }}
                                </p>
                                <div class="flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                                    <span class="flex items-center gap-1">
                                        <x-heroicon-o-globe-alt class="h-3 w-3" />
                                        {{ $tenant->getPrimaryDomain() ?? 'No domain' }}
                                    </span>
                                    <span class="flex items-center gap-1">
                                        <x-heroicon-o-tag class="h-3 w-3" />
                                        {{ ucfirst($tenant->getSubscriptionPlan()) }}
                                    </span>
                                    @if($tenant->isOnTrial())
                                        <span class="flex items-center gap-1 text-amber-600">
                                            <x-heroicon-o-clock class="h-3 w-3" />
                                            Trial
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex items-center gap-2">
                            @if($this->getCurrentTenant() && $this->getCurrentTenant()->id === $tenant->id)
                                <x-filament::badge color="success" size="sm">
                                    Active
                                </x-filament::badge>
                            @else
                                <x-filament::button 
                                    wire:click="switchTenant('{{ $tenant->id }}')" 
                                    size="sm"
                                    outlined
                                >
                                    Switch
                                </x-filament::button>
                            @endif
                            
                            @if($tenant->getPrimaryDomain())
                                <x-filament::button 
                                    href="http://{{ $tenant->getPrimaryDomain() }}/admin"
                                    target="_blank"
                                    size="sm"
                                    color="gray"
                                    outlined
                                >
                                    <x-heroicon-o-arrow-top-right-on-square class="h-4 w-4" />
                                </x-filament::button>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="p-4 text-center text-gray-500 dark:text-gray-400">
                        <x-heroicon-o-building-office-2 class="h-8 w-8 mx-auto mb-2 opacity-50" />
                        <p class="text-sm">No tenants available</p>
                    </div>
                @endforelse
            </div>
        </div>
    </x-filament::section>

    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('tenant-switched', (event) => {
                new FilamentNotification()
                    .title('Tenant Switched')
                    .body(`Now managing: ${event.tenant}`)
                    .success()
                    .send();
            });

            Livewire.on('tenant-cleared', () => {
                new FilamentNotification()
                    .title('Tenant Cleared')
                    .body('No longer managing any specific tenant')
                    .success()
                    .send();
            });

            Livewire.on('tenant-access-denied', () => {
                new FilamentNotification()
                    .title('Access Denied')
                    .body('You do not have permission to access this tenant')
                    .danger()
                    .send();
            });
        });
    </script>
</x-filament-widgets::widget>

