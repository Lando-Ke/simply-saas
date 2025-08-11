<?php

namespace Tests\Unit;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingModelsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed roles and permissions
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
    }

    // Plan Tests
    public function test_plan_can_be_created()
    {
        $plan = Plan::create([
            'name' => 'Basic Plan',
            'slug' => 'basic',
            'price' => 29.99,
            'billing_cycle' => 'monthly',
            'features' => ['feature1', 'feature2'],
        ]);

        $this->assertInstanceOf(Plan::class, $plan);
        $this->assertEquals('Basic Plan', $plan->name);
        $this->assertEquals(29.99, $plan->price);
    }

    public function test_plan_price_calculations()
    {
        $monthlyPlan = Plan::create([
            'name' => 'Monthly Plan',
            'price' => 10.00,
            'billing_cycle' => 'monthly',
        ]);

        $yearlyPlan = Plan::create([
            'name' => 'Yearly Plan',
            'price' => 100.00,
            'billing_cycle' => 'yearly',
        ]);

        $this->assertEquals(120.00, $monthlyPlan->getYearlyPrice());
        $this->assertEquals(8.33, round($yearlyPlan->getMonthlyPrice(), 2));
    }

    public function test_plan_feature_checking()
    {
        $plan = Plan::create([
            'name' => 'Feature Plan',
            'price' => 10.00,
            'features' => ['api_access', 'analytics', 'support'],
        ]);

        $this->assertTrue($plan->hasFeature('api_access'));
        $this->assertTrue($plan->hasFeature('analytics'));
        $this->assertFalse($plan->hasFeature('nonexistent'));
    }

    public function test_plan_scopes()
    {
        Plan::create(['name' => 'Free Plan', 'price' => 0, 'is_active' => true]);
        Plan::create(['name' => 'Paid Plan', 'price' => 10, 'is_active' => true]);
        Plan::create(['name' => 'Inactive Plan', 'price' => 20, 'is_active' => false]);

        $this->assertEquals(2, Plan::active()->count());
        $this->assertEquals(1, Plan::free()->count());
        $this->assertEquals(2, Plan::paid()->count());
    }

    // Subscription Tests
    public function test_subscription_can_be_created()
    {
        $user = User::factory()->create();
        $plan = Plan::create(['name' => 'Test Plan', 'price' => 10]);

        $subscription = Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'amount' => 10.00,
            'starts_at' => now(),
        ]);

        $this->assertInstanceOf(Subscription::class, $subscription);
        $this->assertTrue($subscription->isActive());
        $this->assertEquals($user->id, $subscription->user_id);
    }

    public function test_subscription_status_checking()
    {
        $user = User::factory()->create();
        $plan = Plan::create(['name' => 'Test Plan', 'price' => 10]);

        $activeSubscription = Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'amount' => 10.00,
            'starts_at' => now(),
        ]);

        $canceledSubscription = Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'canceled',
            'amount' => 10.00,
            'starts_at' => now(),
        ]);

        $this->assertTrue($activeSubscription->isActive());
        $this->assertFalse($activeSubscription->isCanceled());
        $this->assertTrue($canceledSubscription->isCanceled());
    }

    public function test_subscription_trial_checking()
    {
        $user = User::factory()->create();
        $plan = Plan::create(['name' => 'Test Plan', 'price' => 10]);

        $trialSubscription = Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'trial_ends_at' => now()->addDays(30),
            'amount' => 10.00,
            'starts_at' => now(),
        ]);

        $expiredTrialSubscription = Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'trial_ends_at' => now()->subDays(1),
            'amount' => 10.00,
            'starts_at' => now(),
        ]);

        $this->assertTrue($trialSubscription->isOnTrial());
        $this->assertFalse($expiredTrialSubscription->isOnTrial());
    }

    public function test_subscription_cancellation()
    {
        $user = User::factory()->create();
        $plan = Plan::create(['name' => 'Test Plan', 'price' => 10]);

        $subscription = Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'amount' => 10.00,
            'starts_at' => now(),
        ]);

        $subscription->cancel();

        $this->assertTrue($subscription->isCanceled());
        $this->assertNotNull($subscription->canceled_at);
    }

    public function test_subscription_relationships()
    {
        $user = User::factory()->create();
        $plan = Plan::create(['name' => 'Test Plan', 'price' => 10]);

        $subscription = Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'amount' => 10.00,
            'starts_at' => now(),
        ]);

        $this->assertInstanceOf(User::class, $subscription->user);
        $this->assertInstanceOf(Plan::class, $subscription->plan);
        $this->assertEquals($user->id, $subscription->user->id);
        $this->assertEquals($plan->id, $subscription->plan->id);
    }

    // Invoice Tests
    public function test_invoice_can_be_created()
    {
        $user = User::factory()->create();
        $plan = Plan::create(['name' => 'Test Plan', 'price' => 10]);
        $subscription = Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'amount' => 10.00,
            'starts_at' => now(),
        ]);

        $invoice = Invoice::create([
            'subscription_id' => $subscription->id,
            'user_id' => $user->id,
            'subtotal' => 10.00,
            'tax_amount' => 1.00,
            'total_amount' => 11.00,
            'due_date' => now()->addDays(30),
        ]);

        $this->assertInstanceOf(Invoice::class, $invoice);
        $this->assertEquals(10.00, $invoice->subtotal);
        $this->assertEquals(11.00, $invoice->total_amount);
    }

    public function test_invoice_status_checking()
    {
        $user = User::factory()->create();
        $subscription = Subscription::create([
            'user_id' => $user->id,
            'plan_id' => Plan::create(['name' => 'Test Plan', 'price' => 10])->id,
            'status' => 'active',
            'amount' => 10.00,
            'starts_at' => now(),
        ]);

        $paidInvoice = Invoice::create([
            'subscription_id' => $subscription->id,
            'user_id' => $user->id,
            'status' => 'paid',
            'subtotal' => 10.00,
            'total_amount' => 10.00,
            'due_date' => now()->addDays(30),
        ]);

        $overdueInvoice = Invoice::create([
            'subscription_id' => $subscription->id,
            'user_id' => $user->id,
            'status' => 'sent',
            'subtotal' => 10.00,
            'total_amount' => 10.00,
            'due_date' => now()->subDays(1),
        ]);

        $this->assertTrue($paidInvoice->isPaid());
        $this->assertFalse($paidInvoice->isOverdue());
        $this->assertTrue($overdueInvoice->isOverdue());
    }

    public function test_invoice_calculations()
    {
        $user = User::factory()->create();
        $subscription = Subscription::create([
            'user_id' => $user->id,
            'plan_id' => Plan::create(['name' => 'Test Plan', 'price' => 10])->id,
            'status' => 'active',
            'amount' => 10.00,
            'starts_at' => now(),
        ]);

        $invoice = Invoice::create([
            'subscription_id' => $subscription->id,
            'user_id' => $user->id,
            'subtotal' => 100.00,
            'tax_amount' => 10.00,
            'discount_amount' => 5.00,
            'total_amount' => 105.00,
            'due_date' => now()->addDays(30),
        ]);

        $this->assertEquals(105.00, $invoice->calculateTotal());
        $this->assertEquals('$100.00', $invoice->getDisplaySubtotal());
        $this->assertEquals('$10.00', $invoice->getDisplayTaxAmount());
        $this->assertEquals('$5.00', $invoice->getDisplayDiscountAmount());
    }

    public function test_invoice_line_items()
    {
        $user = User::factory()->create();
        $subscription = Subscription::create([
            'user_id' => $user->id,
            'plan_id' => Plan::create(['name' => 'Test Plan', 'price' => 10])->id,
            'status' => 'active',
            'amount' => 10.00,
            'starts_at' => now(),
        ]);

        $invoice = Invoice::create([
            'subscription_id' => $subscription->id,
            'user_id' => $user->id,
            'subtotal' => 10.00,
            'total_amount' => 10.00,
            'due_date' => now()->addDays(30),
            'line_items' => [
                ['description' => 'Item 1', 'amount' => 5.00],
                ['description' => 'Item 2', 'amount' => 5.00],
            ],
        ]);

        $this->assertCount(2, $invoice->getLineItems());
        
        $invoice->addLineItem(['description' => 'Item 3', 'amount' => 3.00]);
        $this->assertCount(3, $invoice->getLineItems());
    }

    // Payment Tests
    public function test_payment_can_be_created()
    {
        $user = User::factory()->create();
        $subscription = Subscription::create([
            'user_id' => $user->id,
            'plan_id' => Plan::create(['name' => 'Test Plan', 'price' => 10])->id,
            'status' => 'active',
            'amount' => 10.00,
            'starts_at' => now(),
        ]);
        $invoice = Invoice::create([
            'subscription_id' => $subscription->id,
            'user_id' => $user->id,
            'subtotal' => 10.00,
            'total_amount' => 10.00,
            'due_date' => now()->addDays(30),
        ]);

        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'user_id' => $user->id,
            'payment_method' => 'stripe',
            'amount' => 10.00,
            'status' => 'completed',
        ]);

        $this->assertInstanceOf(Payment::class, $payment);
        $this->assertEquals(10.00, $payment->amount);
        $this->assertTrue($payment->isCompleted());
    }

    public function test_payment_status_checking()
    {
        $user = User::factory()->create();
        $invoice = Invoice::create([
            'subscription_id' => Subscription::create([
                'user_id' => $user->id,
                'plan_id' => Plan::create(['name' => 'Test Plan', 'price' => 10])->id,
                'status' => 'active',
                'amount' => 10.00,
                'starts_at' => now(),
            ])->id,
            'user_id' => $user->id,
            'subtotal' => 10.00,
            'total_amount' => 10.00,
            'due_date' => now()->addDays(30),
        ]);

        $completedPayment = Payment::create([
            'invoice_id' => $invoice->id,
            'user_id' => $user->id,
            'status' => 'completed',
            'amount' => 10.00,
        ]);

        $failedPayment = Payment::create([
            'invoice_id' => $invoice->id,
            'user_id' => $user->id,
            'status' => 'failed',
            'amount' => 10.00,
        ]);

        $this->assertTrue($completedPayment->isCompleted());
        $this->assertFalse($completedPayment->isFailed());
        $this->assertTrue($failedPayment->isFailed());
    }

    public function test_payment_method_checking()
    {
        $user = User::factory()->create();
        $invoice = Invoice::create([
            'subscription_id' => Subscription::create([
                'user_id' => $user->id,
                'plan_id' => Plan::create(['name' => 'Test Plan', 'price' => 10])->id,
                'status' => 'active',
                'amount' => 10.00,
                'starts_at' => now(),
            ])->id,
            'user_id' => $user->id,
            'subtotal' => 10.00,
            'total_amount' => 10.00,
            'due_date' => now()->addDays(30),
        ]);

        $stripePayment = Payment::create([
            'invoice_id' => $invoice->id,
            'user_id' => $user->id,
            'payment_method' => 'stripe',
            'amount' => 10.00,
        ]);

        $paypalPayment = Payment::create([
            'invoice_id' => $invoice->id,
            'user_id' => $user->id,
            'payment_method' => 'paypal',
            'amount' => 10.00,
        ]);

        $this->assertTrue($stripePayment->isStripe());
        $this->assertFalse($stripePayment->isPayPal());
        $this->assertTrue($paypalPayment->isPayPal());
    }

    public function test_payment_relationships()
    {
        $user = User::factory()->create();
        $subscription = Subscription::create([
            'user_id' => $user->id,
            'plan_id' => Plan::create(['name' => 'Test Plan', 'price' => 10])->id,
            'status' => 'active',
            'amount' => 10.00,
            'starts_at' => now(),
        ]);
        $invoice = Invoice::create([
            'subscription_id' => $subscription->id,
            'user_id' => $user->id,
            'subtotal' => 10.00,
            'total_amount' => 10.00,
            'due_date' => now()->addDays(30),
        ]);

        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'user_id' => $user->id,
            'amount' => 10.00,
        ]);

        $this->assertInstanceOf(Invoice::class, $payment->invoice);
        $this->assertInstanceOf(User::class, $payment->user);
        $this->assertEquals($invoice->id, $payment->invoice->id);
        $this->assertEquals($user->id, $payment->user->id);
    }

    public function test_payment_processing()
    {
        $user = User::factory()->create();
        $invoice = Invoice::create([
            'subscription_id' => Subscription::create([
                'user_id' => $user->id,
                'plan_id' => Plan::create(['name' => 'Test Plan', 'price' => 10])->id,
                'status' => 'active',
                'amount' => 10.00,
                'starts_at' => now(),
            ])->id,
            'user_id' => $user->id,
            'subtotal' => 10.00,
            'total_amount' => 10.00,
            'due_date' => now()->addDays(30),
        ]);

        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'user_id' => $user->id,
            'amount' => 10.00,
        ]);

        $payment->markAsCompleted();

        $this->assertTrue($payment->isCompleted());
        $this->assertNotNull($payment->processed_at);
    }
}
