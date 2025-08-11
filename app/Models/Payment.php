<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'user_id',
        'payment_method',
        'status',
        'amount',
        'currency',
        'transaction_id',
        'stripe_payment_intent_id',
        'payment_details',
        'metadata',
        'processed_at',
        'failure_reason',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_details' => 'array',
        'metadata' => 'array',
        'processed_at' => 'datetime',
    ];

    /**
     * Get the invoice that owns the payment
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the user that owns the payment
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if payment is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if payment is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if payment is processing
     */
    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    /**
     * Check if payment failed
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Check if payment is refunded
     */
    public function isRefunded(): bool
    {
        return $this->status === 'refunded';
    }

    /**
     * Get payment amount for display
     */
    public function getDisplayAmount(): string
    {
        return '$' . number_format($this->amount, 2);
    }

    /**
     * Get payment method display name
     */
    public function getPaymentMethodDisplay(): string
    {
        return match($this->payment_method) {
            'stripe' => 'Credit Card',
            'paypal' => 'PayPal',
            'bank_transfer' => 'Bank Transfer',
            default => ucfirst(str_replace('_', ' ', $this->payment_method)),
        };
    }

    /**
     * Get payment status for display
     */
    public function getStatusDisplay(): string
    {
        return match($this->status) {
            'pending' => 'Pending',
            'processing' => 'Processing',
            'completed' => 'Completed',
            'failed' => 'Failed',
            'refunded' => 'Refunded',
            default => ucfirst($this->status),
        };
    }

    /**
     * Get payment status color
     */
    public function getStatusColor(): string
    {
        return match($this->status) {
            'pending' => 'warning',
            'processing' => 'info',
            'completed' => 'success',
            'failed' => 'danger',
            'refunded' => 'secondary',
            default => 'secondary',
        };
    }

    /**
     * Mark payment as completed
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'processed_at' => now(),
        ]);
    }

    /**
     * Mark payment as failed
     */
    public function markAsFailed(string $reason = null): void
    {
        $this->update([
            'status' => 'failed',
            'failure_reason' => $reason,
        ]);
    }

    /**
     * Mark payment as refunded
     */
    public function markAsRefunded(): void
    {
        $this->update([
            'status' => 'refunded',
        ]);
    }

    /**
     * Get payment processing time
     */
    public function getProcessingTime(): ?int
    {
        if (!$this->processed_at) {
            return null;
        }

        return $this->created_at->diffInSeconds($this->processed_at);
    }

    /**
     * Get payment details
     */
    public function getPaymentDetails(): array
    {
        return $this->payment_details ?? [];
    }

    /**
     * Set payment details
     */
    public function setPaymentDetails(array $details): void
    {
        $this->update(['payment_details' => $details]);
    }

    /**
     * Get payment metadata
     */
    public function getMetadata(): array
    {
        return $this->metadata ?? [];
    }

    /**
     * Set payment metadata
     */
    public function setMetadata(array $metadata): void
    {
        $this->update(['metadata' => $metadata]);
    }

    /**
     * Check if payment is from Stripe
     */
    public function isStripe(): bool
    {
        return $this->payment_method === 'stripe';
    }

    /**
     * Check if payment is from PayPal
     */
    public function isPayPal(): bool
    {
        return $this->payment_method === 'paypal';
    }

    /**
     * Check if payment is bank transfer
     */
    public function isBankTransfer(): bool
    {
        return $this->payment_method === 'bank_transfer';
    }

    /**
     * Get Stripe payment intent ID
     */
    public function getStripePaymentIntentId(): ?string
    {
        return $this->stripe_payment_intent_id;
    }

    /**
     * Set Stripe payment intent ID
     */
    public function setStripePaymentIntentId(string $intentId): void
    {
        $this->update(['stripe_payment_intent_id' => $intentId]);
    }

    /**
     * Scope for completed payments
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for pending payments
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for failed payments
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope for refunded payments
     */
    public function scopeRefunded($query)
    {
        return $query->where('status', 'refunded');
    }

    /**
     * Scope for Stripe payments
     */
    public function scopeStripe($query)
    {
        return $query->where('payment_method', 'stripe');
    }

    /**
     * Scope for PayPal payments
     */
    public function scopePayPal($query)
    {
        return $query->where('payment_method', 'paypal');
    }

    /**
     * Get payment by transaction ID
     */
    public static function findByTransactionId(string $transactionId): ?self
    {
        return static::where('transaction_id', $transactionId)->first();
    }

    /**
     * Get payment by Stripe payment intent ID
     */
    public static function findByStripePaymentIntentId(string $intentId): ?self
    {
        return static::where('stripe_payment_intent_id', $intentId)->first();
    }

    /**
     * Get total payments for user
     */
    public static function getTotalForUser(int $userId): float
    {
        return static::where('user_id', $userId)
            ->where('status', 'completed')
            ->sum('amount');
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Set default status if not provided
        static::creating(function ($payment) {
            if (empty($payment->status)) {
                $payment->status = 'pending';
            }
        });
    }
}
