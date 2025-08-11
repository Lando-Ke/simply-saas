<?php

namespace Tests\Unit\Domain\Services;

use App\Domain\Services\BillingCalculationService;
use App\Domain\ValueObjects\Money;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Invoice;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingCalculationServiceTest extends TestCase
{
    use RefreshDatabase;

    private BillingCalculationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new BillingCalculationService();
    }

    public function test_calculate_subscription_amount_for_monthly_plan()
    {
        $plan = Plan::create([
            'name' => 'Monthly Plan',
            'price' => 29.99,
            'billing_cycle' => 'monthly'
        ]);

        $amount = $this->service->calculateSubscriptionAmount($plan, 'monthly');
        
        $this->assertEquals(29.99, $amount->getAmount());
        $this->assertEquals('USD', $amount->getCurrency());
    }

    public function test_calculate_subscription_amount_for_yearly_plan()
    {
        $plan = Plan::create([
            'name' => 'Yearly Plan',
            'price' => 299.99,
            'billing_cycle' => 'yearly'
        ]);

        $amount = $this->service->calculateSubscriptionAmount($plan, 'yearly');
        
        $this->assertEquals(299.99, $amount->getAmount());
        $this->assertEquals('USD', $amount->getCurrency());
    }

    public function test_calculate_subscription_amount_with_billing_cycle_conversion()
    {
        $monthlyPlan = Plan::create([
            'name' => 'Monthly Plan',
            'price' => 10.00,
            'billing_cycle' => 'monthly'
        ]);

        $yearlyAmount = $this->service->calculateSubscriptionAmount($monthlyPlan, 'yearly');
        $this->assertEquals(120.00, $yearlyAmount->getAmount());

        $yearlyPlan = Plan::create([
            'name' => 'Yearly Plan',
            'price' => 120.00,
            'billing_cycle' => 'yearly'
        ]);

        $monthlyAmount = $this->service->calculateSubscriptionAmount($yearlyPlan, 'monthly');
        $this->assertEquals(10.00, $monthlyAmount->getAmount());
    }

    public function test_calculate_proration_for_plan_upgrade()
    {
        $user = User::factory()->create();
        $currentPlan = Plan::create([
            'name' => 'Basic Plan',
            'price' => 10.00,
            'billing_cycle' => 'monthly'
        ]);
        $newPlan = Plan::create([
            'name' => 'Premium Plan',
            'price' => 20.00,
            'billing_cycle' => 'monthly'
        ]);

        $subscription = Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $currentPlan->id,
            'status' => 'active',
            'amount' => 10.00,
            'starts_at' => now()->subDays(15),
            'ends_at' => now()->addDays(15)
        ]);

        $proration = $this->service->calculateProration($subscription, $newPlan);
        
        // Should be positive (customer owes more for upgrade)
        $this->assertTrue($proration->getAmount() > 0);
    }

    public function test_calculate_proration_for_plan_downgrade()
    {
        $user = User::factory()->create();
        $currentPlan = Plan::create([
            'name' => 'Premium Plan',
            'price' => 20.00,
            'billing_cycle' => 'monthly'
        ]);
        $newPlan = Plan::create([
            'name' => 'Basic Plan',
            'price' => 10.00,
            'billing_cycle' => 'monthly'
        ]);

        $subscription = Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $currentPlan->id,
            'status' => 'active',
            'amount' => 20.00,
            'starts_at' => now()->subDays(15),
            'ends_at' => now()->addDays(15)
        ]);

        $proration = $this->service->calculateProration($subscription, $newPlan);
        
        // Should be zero (customer gets no credit for downgrade in this implementation)
        $this->assertTrue($proration->getAmount() == 0);
    }

    public function test_calculate_tax_amount()
    {
        $subtotal = new Money(100.00);
        $taxRate = 0.08; // 8%
        
        $tax = $this->service->calculateTax($subtotal, $taxRate);
        
        $this->assertEquals(8.00, $tax->getAmount());
    }

    public function test_calculate_tax_amount_with_default_rate()
    {
        $subtotal = new Money(100.00);
        
        $tax = $this->service->calculateTax($subtotal);
        
        $this->assertEquals(0.00, $tax->getAmount());
    }

    public function test_calculate_tax_throws_exception_for_invalid_rate()
    {
        $subtotal = new Money(100.00);
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Tax rate must be between 0 and 1');
        
        $this->service->calculateTax($subtotal, 1.5);
    }

    public function test_calculate_discount_amount()
    {
        $subtotal = new Money(100.00);
        $discountPercentage = 0.10; // 10%
        
        $discount = $this->service->calculateDiscount($subtotal, $discountPercentage);
        
        $this->assertEquals(10.00, $discount->getAmount());
    }

    public function test_calculate_discount_throws_exception_for_invalid_percentage()
    {
        $subtotal = new Money(100.00);
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Discount percentage must be between 0 and 1');
        
        $this->service->calculateDiscount($subtotal, 1.5);
    }

    public function test_calculate_total_with_tax_and_discount()
    {
        $subtotal = new Money(100.00);
        $taxRate = 0.08; // 8%
        $discountPercentage = 0.10; // 10%
        
        $total = $this->service->calculateTotal($subtotal, $taxRate, $discountPercentage);
        
        // Subtotal: 100.00
        // Discount: 10.00 (10%)
        // Taxable: 90.00
        // Tax: 7.20 (8% of 90.00)
        // Total: 97.20
        $this->assertEquals(97.20, $total->getAmount());
    }

    public function test_calculate_invoice_totals()
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
            'tax_amount' => 8.00,
            'discount_amount' => 10.00,
            'total_amount' => 98.00,
            'due_date' => now()->addDays(30),
        ]);

        $totals = $this->service->calculateInvoiceTotals($invoice);
        
        $this->assertEquals(100.00, $totals['subtotal']->getAmount());
        $this->assertEquals(8.00, $totals['tax_amount']->getAmount());
        $this->assertEquals(10.00, $totals['discount_amount']->getAmount());
        $this->assertEquals(98.00, $totals['total_amount']->getAmount());
    }

    public function test_calculate_arr_for_monthly_subscription()
    {
        $user = User::factory()->create();
        $plan = Plan::create([
            'name' => 'Monthly Plan',
            'price' => 10.00,
            'billing_cycle' => 'monthly'
        ]);

        $subscription = Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'amount' => 10.00,
            'starts_at' => now(),
        ]);

        $arr = $this->service->calculateARR($subscription);
        
        $this->assertEquals(120.00, $arr->getAmount());
    }

    public function test_calculate_arr_for_yearly_subscription()
    {
        $user = User::factory()->create();
        $plan = Plan::create([
            'name' => 'Yearly Plan',
            'price' => 120.00,
            'billing_cycle' => 'yearly'
        ]);

        $subscription = Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'amount' => 120.00,
            'starts_at' => now(),
        ]);

        $arr = $this->service->calculateARR($subscription);
        
        $this->assertEquals(120.00, $arr->getAmount());
    }

    public function test_calculate_mrr_for_monthly_subscription()
    {
        $user = User::factory()->create();
        $plan = Plan::create([
            'name' => 'Monthly Plan',
            'price' => 10.00,
            'billing_cycle' => 'monthly'
        ]);

        $subscription = Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'amount' => 10.00,
            'starts_at' => now(),
        ]);

        $mrr = $this->service->calculateMRR($subscription);
        
        $this->assertEquals(10.00, $mrr->getAmount());
    }

    public function test_calculate_mrr_for_yearly_subscription()
    {
        $user = User::factory()->create();
        $plan = Plan::create([
            'name' => 'Yearly Plan',
            'price' => 120.00,
            'billing_cycle' => 'yearly'
        ]);

        $subscription = Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'amount' => 120.00,
            'starts_at' => now(),
        ]);

        $mrr = $this->service->calculateMRR($subscription);
        
        $this->assertEquals(10.00, $mrr->getAmount());
    }

    public function test_calculate_trial_end_date()
    {
        $startDate = Carbon::parse('2024-01-01');
        $trialDays = 14;
        
        $trialEndDate = $this->service->calculateTrialEndDate($startDate, $trialDays);
        
        $this->assertEquals('2024-01-15', $trialEndDate->format('Y-m-d'));
    }

    public function test_calculate_next_billing_date_for_monthly()
    {
        $user = User::factory()->create();
        $plan = Plan::create([
            'name' => 'Monthly Plan',
            'price' => 10.00,
            'billing_cycle' => 'monthly'
        ]);

        $subscription = Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'amount' => 10.00,
            'starts_at' => Carbon::parse('2024-01-01'),
        ]);

        $nextBillingDate = $this->service->calculateNextBillingDate($subscription);
        
        $this->assertEquals('2024-02-01', $nextBillingDate->format('Y-m-d'));
    }

    public function test_calculate_next_billing_date_for_yearly()
    {
        $user = User::factory()->create();
        $plan = Plan::create([
            'name' => 'Yearly Plan',
            'price' => 120.00,
            'billing_cycle' => 'yearly'
        ]);

        $subscription = Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'amount' => 120.00,
            'starts_at' => Carbon::parse('2024-01-01'),
        ]);

        $nextBillingDate = $this->service->calculateNextBillingDate($subscription);
        
        $this->assertEquals('2025-01-01', $nextBillingDate->format('Y-m-d'));
    }

    public function test_calculate_refund_amount()
    {
        $user = User::factory()->create();
        $plan = Plan::create([
            'name' => 'Monthly Plan',
            'price' => 30.00,
            'billing_cycle' => 'monthly'
        ]);

        $subscription = Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'amount' => 30.00,
            'starts_at' => Carbon::parse('2024-01-01'),
            'ends_at' => Carbon::parse('2024-01-31')
        ]);

        $refundDate = Carbon::parse('2024-01-15');
        $refundAmount = $this->service->calculateRefundAmount($subscription, $refundDate);
        
        // 16 days remaining out of 30 days = 16/30 * 30 = 16.00
        $this->assertEquals(16.00, $refundAmount->getAmount());
    }

    public function test_validate_calculation()
    {
        $subtotal = new Money(100.00);
        $tax = new Money(8.00);
        $discount = new Money(10.00);
        $total = new Money(98.00);
        
        $isValid = $this->service->validateCalculation($subtotal, $tax, $discount, $total);
        
        $this->assertTrue($isValid);
    }

    public function test_validate_calculation_with_invalid_total()
    {
        $subtotal = new Money(100.00);
        $tax = new Money(8.00);
        $discount = new Money(10.00);
        $total = new Money(100.00); // Should be 98.00
        
        $isValid = $this->service->validateCalculation($subtotal, $tax, $discount, $total);
        
        $this->assertFalse($isValid);
    }
}
