<div>
    <div class="mb-6">
        <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">Subscription Management</h2>
        <p class="text-gray-600 dark:text-gray-400 mt-2">Manage your subscription plan and billing.</p>
    </div>

    @if (session()->has("message"))
        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
            {{ session("message") }}
        </div>
    @endif

    @if (session()->has("error"))
        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
            {{ session("error") }}
        </div>
    @endif

    <!-- Current Subscription -->
    @if($currentSubscription)
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg mb-6">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Current Plan</h3>
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="text-xl font-semibold text-gray-900 dark:text-white">
                            {{ $currentSubscription->plan->name }}
                        </h4>
                        <p class="text-gray-600 dark:text-gray-400">
                            ${{ number_format($currentSubscription->amount, 2) }}/{{ $currentSubscription->plan->billing_cycle }}
                        </p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Next billing: {{ $currentSubscription->ends_at ? $currentSubscription->ends_at->format("M j, Y") : "N/A" }}
                        </p>
                    </div>
                    <div class="flex space-x-3">
                        <button wire:click="togglePlanSelection" 
                                class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                            Change Plan
                        </button>
                        <button wire:click="cancelSubscription" 
                                class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg mb-6">
            <div class="px-4 py-5 sm:p-6 text-center">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No Active Subscription</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-4">Choose a plan to get started.</p>
                <button wire:click="togglePlanSelection" 
                        class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                    Choose Plan
                </button>
            </div>
        </div>
    @endif

    <!-- Available Plans -->
    @if($showPlanSelection && $availablePlans->count() > 0)
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Available Plans</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($availablePlans as $plan)
                        <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-4 {{ $plan->is_featured ? "ring-2 ring-indigo-500" : "" }}">
                            @if($plan->is_featured)
                                <span class="bg-indigo-100 text-indigo-800 text-xs px-2 py-1 rounded-full">Popular</span>
                            @endif
                            <h4 class="text-lg font-semibold text-gray-900 dark:text-white mt-2">{{ $plan->name }}</h4>
                            <p class="text-gray-600 dark:text-gray-400 text-sm">{{ $plan->description }}</p>
                            <div class="mt-4">
                                <span class="text-2xl font-bold text-gray-900 dark:text-white">${{ number_format($plan->price, 2) }}</span>
                                <span class="text-gray-500 dark:text-gray-400">/ {{ $plan->billing_cycle }}</span>
                            </div>
                            <button wire:click="subscribeToPlan({{ $plan->id }})" 
                                    class="w-full mt-4 px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 
                                           {{ $currentSubscription && $currentSubscription->plan_id === $plan->id ? "opacity-50 cursor-not-allowed" : "" }}"
                                    {{ $currentSubscription && $currentSubscription->plan_id === $plan->id ? "disabled" : "" }}>
                                {{ $currentSubscription && $currentSubscription->plan_id === $plan->id ? "Current Plan" : "Select Plan" }}
                            </button>
                        </div>
                    @endforeach
                </div>
                <div class="mt-4">
                    <button wire:click="togglePlanSelection" 
                            class="px-4 py-2 bg-gray-300 dark:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-400 dark:hover:bg-gray-500">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
