<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'plan_id',
        'status',
        'stripe_subscription_id',
        'stripe_customer_id',
        'amount',
        'currency',
        'trial_ends_at',
        'starts_at',
        'ends_at',
        'canceled_at',
        'cancel_at_period_end',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'trial_ends_at' => 'date',
        'starts_at' => 'date',
        'ends_at' => 'date',
        'canceled_at' => 'date',
        'cancel_at_period_end' => 'date',
        'metadata' => 'array',
    ];

    /**
     * Get the user that owns the subscription
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the plan for this subscription
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Get the invoices for this subscription
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Check if subscription is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if subscription is canceled
     */
    public function isCanceled(): bool
    {
        return $this->status === 'canceled';
    }

    /**
     * Check if subscription is past due
     */
    public function isPastDue(): bool
    {
        return $this->status === 'past_due';
    }

    /**
     * Check if subscription is on trial
     */
    public function isOnTrial(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    /**
     * Check if subscription has ended
     */
    public function hasEnded(): bool
    {
        return $this->ends_at && $this->ends_at->isPast();
    }

    /**
     * Check if subscription will be canceled at period end
     */
    public function willCancelAtPeriodEnd(): bool
    {
        return $this->cancel_at_period_end && $this->cancel_at_period_end->isFuture();
    }

    /**
     * Get subscription end date
     */
    public function getEndDate(): ?Carbon
    {
        if ($this->ends_at) {
            return $this->ends_at;
        }

        if ($this->cancel_at_period_end) {
            return $this->cancel_at_period_end;
        }

        return null;
    }

    /**
     * Get days until subscription ends
     */
    public function getDaysUntilEnd(): ?int
    {
        $endDate = $this->getEndDate();
        
        if (!$endDate) {
            return null;
        }

        return now()->diffInDays($endDate, false);
    }

    /**
     * Get subscription amount for display
     */
    public function getDisplayAmount(): string
    {
        return '$' . number_format($this->amount, 2);
    }

    /**
     * Get subscription period
     */
    public function getPeriod(): string
    {
        $start = $this->starts_at->format('M j, Y');
        $end = $this->getEndDate()?->format('M j, Y') ?? 'Ongoing';
        
        return $start . ' - ' . $end;
    }

    /**
     * Cancel subscription
     */
    public function cancel(): void
    {
        $this->update([
            'status' => 'canceled',
            'canceled_at' => now(),
            'ends_at' => now(),
        ]);
    }

    /**
     * Cancel subscription at period end
     */
    public function cancelAtPeriodEnd(): void
    {
        $this->update([
            'cancel_at_period_end' => $this->getEndDate(),
        ]);
    }

    /**
     * Reactivate subscription
     */
    public function reactivate(): void
    {
        $this->update([
            'status' => 'active',
            'canceled_at' => null,
            'cancel_at_period_end' => null,
        ]);
    }

    /**
     * Get subscription status for display
     */
    public function getStatusDisplay(): string
    {
        return match($this->status) {
            'active' => 'Active',
            'canceled' => 'Canceled',
            'past_due' => 'Past Due',
            'unpaid' => 'Unpaid',
            default => ucfirst($this->status),
        };
    }

    /**
     * Get subscription status color
     */
    public function getStatusColor(): string
    {
        return match($this->status) {
            'active' => 'success',
            'canceled' => 'danger',
            'past_due' => 'warning',
            'unpaid' => 'danger',
            default => 'secondary',
        };
    }

    /**
     * Scope for active subscriptions
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for canceled subscriptions
     */
    public function scopeCanceled($query)
    {
        return $query->where('status', 'canceled');
    }

    /**
     * Scope for trial subscriptions
     */
    public function scopeOnTrial($query)
    {
        return $query->where('trial_ends_at', '>', now());
    }

    /**
     * Scope for expired subscriptions
     */
    public function scopeExpired($query)
    {
        return $query->where('ends_at', '<', now());
    }

    /**
     * Get subscription by Stripe ID
     */
    public static function findByStripeId(string $stripeId): ?self
    {
        return static::where('stripe_subscription_id', $stripeId)->first();
    }

    /**
     * Get active subscription for user
     */
    public static function getActiveForUser(int $userId): ?self
    {
        return static::where('user_id', $userId)
            ->where('status', 'active')
            ->first();
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Set default start date if not provided
        static::creating(function ($subscription) {
            if (empty($subscription->starts_at)) {
                $subscription->starts_at = now();
            }
        });
    }
}
