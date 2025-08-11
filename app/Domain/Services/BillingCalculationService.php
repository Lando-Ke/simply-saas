<?php

namespace App\Domain\Services;

use App\Domain\ValueObjects\Money;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Invoice;
use Carbon\Carbon;

class BillingCalculationService
{
    private const DEFAULT_TAX_RATE = 0.0;
    private const DEFAULT_CURRENCY = 'USD';

    /**
     * Calculate subscription amount based on plan and billing cycle
     */
    public function calculateSubscriptionAmount(Plan $plan, string $billingCycle = 'monthly'): Money
    {
        $baseAmount = $plan->price;
        
        if ($billingCycle === 'yearly' && $plan->billing_cycle === 'monthly') {
            $baseAmount = $plan->getYearlyPrice();
        } elseif ($billingCycle === 'monthly' && $plan->billing_cycle === 'yearly') {
            $baseAmount = $plan->getMonthlyPrice();
        }

        return new Money($baseAmount, self::DEFAULT_CURRENCY);
    }

    /**
     * Calculate proration for plan changes
     */
    public function calculateProration(
        Subscription $subscription,
        Plan $newPlan,
        Carbon $changeDate = null
    ): Money {
        $changeDate = $changeDate ?? now();
        $currentPlan = $subscription->plan;
        
        // Calculate remaining time on current subscription
        $endDate = $subscription->getEndDate() ?? $changeDate->copy()->addMonth();
        $remainingDays = $changeDate->diffInDays($endDate);
        $totalDays = $subscription->starts_at->diffInDays($endDate);
        
        if ($remainingDays <= 0 || $totalDays <= 0) {
            return Money::zero();
        }

        // Calculate unused amount from current plan
        $currentAmount = new Money($currentPlan->price);
        $unusedRatio = $remainingDays / $totalDays;
        $unusedAmount = $currentAmount->multiply($unusedRatio);

        // Calculate new plan amount for remaining period
        $newAmount = $this->calculateSubscriptionAmount($newPlan);
        $newAmountForRemainingPeriod = $newAmount->multiply($unusedRatio);

        // Proration is the difference
        $difference = $newAmountForRemainingPeriod->getAmount() - $unusedAmount->getAmount();
        
        if ($difference >= 0) {
            return new Money($difference, self::DEFAULT_CURRENCY);
        } else {
            // For negative proration (downgrades), return zero instead of throwing exception
            return Money::zero(self::DEFAULT_CURRENCY);
        }
    }

    /**
     * Calculate tax amount
     */
    public function calculateTax(Money $subtotal, float $taxRate = null): Money
    {
        $taxRate = $taxRate ?? self::DEFAULT_TAX_RATE;
        
        if ($taxRate < 0 || $taxRate > 1) {
            throw new \InvalidArgumentException('Tax rate must be between 0 and 1');
        }

        return $subtotal->multiply($taxRate);
    }

    /**
     * Calculate discount amount
     */
    public function calculateDiscount(Money $subtotal, float $discountPercentage = 0): Money
    {
        if ($discountPercentage < 0 || $discountPercentage > 1) {
            throw new \InvalidArgumentException('Discount percentage must be between 0 and 1');
        }

        return $subtotal->multiply($discountPercentage);
    }

    /**
     * Calculate total amount including tax and discount
     */
    public function calculateTotal(
        Money $subtotal,
        float $taxRate = null,
        float $discountPercentage = 0
    ): Money {
        $discount = $this->calculateDiscount($subtotal, $discountPercentage);
        $taxableAmount = $subtotal->subtract($discount);
        $tax = $this->calculateTax($taxableAmount, $taxRate);

        return $taxableAmount->add($tax);
    }

    /**
     * Calculate invoice totals
     */
    public function calculateInvoiceTotals(Invoice $invoice): array
    {
        $subtotal = new Money($invoice->subtotal);
        $taxAmount = new Money($invoice->tax_amount);
        $discountAmount = new Money($invoice->discount_amount);
        $totalAmount = new Money($invoice->total_amount);

        return [
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'discount_amount' => $discountAmount,
            'total_amount' => $totalAmount,
            'calculated_total' => $this->calculateTotal(
                $subtotal,
                $taxAmount->getAmount() / $subtotal->getAmount(),
                $discountAmount->getAmount() / $subtotal->getAmount()
            )
        ];
    }

    /**
     * Calculate annual recurring revenue (ARR)
     */
    public function calculateARR(Subscription $subscription): Money
    {
        $monthlyAmount = new Money($subscription->amount);
        
        if ($subscription->plan->billing_cycle === 'yearly') {
            return $monthlyAmount;
        }

        return $monthlyAmount->multiply(12);
    }

    /**
     * Calculate monthly recurring revenue (MRR)
     */
    public function calculateMRR(Subscription $subscription): Money
    {
        $amount = new Money($subscription->amount);
        
        if ($subscription->plan->billing_cycle === 'monthly') {
            return $amount;
        }

        return $amount->divide(12);
    }

    /**
     * Calculate trial end date
     */
    public function calculateTrialEndDate(Carbon $startDate, int $trialDays): Carbon
    {
        return $startDate->copy()->addDays($trialDays);
    }

    /**
     * Calculate next billing date
     */
    public function calculateNextBillingDate(Subscription $subscription): Carbon
    {
        $plan = $subscription->plan;
        $lastBillingDate = $subscription->starts_at;
        
        if ($plan->billing_cycle === 'monthly') {
            return $lastBillingDate->copy()->addMonth();
        } elseif ($plan->billing_cycle === 'yearly') {
            return $lastBillingDate->copy()->addYear();
        }

        throw new \InvalidArgumentException("Unsupported billing cycle: {$plan->billing_cycle}");
    }

    /**
     * Calculate refund amount
     */
    public function calculateRefundAmount(Subscription $subscription, Carbon $refundDate = null): Money
    {
        $refundDate = $refundDate ?? now();
        $endDate = $subscription->getEndDate();
        
        if (!$endDate || $refundDate->gte($endDate)) {
            return Money::zero();
        }

        $remainingDays = $refundDate->diffInDays($endDate);
        $totalDays = $subscription->starts_at->diffInDays($endDate);
        
        if ($remainingDays <= 0 || $totalDays <= 0) {
            return Money::zero();
        }

        $monthlyAmount = new Money($subscription->amount);
        $unusedRatio = $remainingDays / $totalDays;
        
        return $monthlyAmount->multiply($unusedRatio);
    }

    /**
     * Validate billing calculation
     */
    public function validateCalculation(Money $subtotal, Money $tax, Money $discount, Money $total): bool
    {
        $calculatedTotal = $subtotal->add($tax)->subtract($discount);
        return $calculatedTotal->equals($total);
    }
}
