<?php

namespace App\Livewire\Billing;

use Livewire\Component;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Support\Facades\Auth;

class SubscriptionManager extends Component
{
    public $currentSubscription = null;
    public $availablePlans = [];
    public $showPlanSelection = false;

    public function mount()
    {
        $this->loadSubscriptionData();
    }

    public function loadSubscriptionData()
    {
        $user = Auth::user();
        
        // Get current subscription
        $this->currentSubscription = Subscription::where('user_id', $user->id)
            ->where('status', 'active')
            ->with('plan')
            ->first();

        // Get available plans
        $this->availablePlans = Plan::where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    public function togglePlanSelection()
    {
        $this->showPlanSelection = !$this->showPlanSelection;
    }

    public function subscribeToPlan($planId)
    {
        $plan = Plan::find($planId);
        if (!$plan) {
            session()->flash('error', 'Plan not found.');
            return;
        }

        // Cancel existing subscription if any
        if ($this->currentSubscription) {
            $this->currentSubscription->update([
                'status' => 'canceled',
                'canceled_at' => now()
            ]);
        }

        // Create new subscription
        Subscription::create([
            'user_id' => Auth::id(),
            'plan_id' => $planId,
            'status' => 'active',
            'amount' => $plan->price,
            'currency' => 'USD',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(), // Simple monthly billing
        ]);

        $this->loadSubscriptionData();
        $this->showPlanSelection = false;
        
        session()->flash('message', 'Successfully subscribed to ' . $plan->name . '!');
    }

    public function cancelSubscription()
    {
        if ($this->currentSubscription) {
            $this->currentSubscription->update([
                'status' => 'canceled',
                'canceled_at' => now()
            ]);

            $this->loadSubscriptionData();
            session()->flash('message', 'Subscription canceled successfully.');
        }
    }

    public function render()
    {
        return view('livewire.billing.subscription-manager');
    }
}