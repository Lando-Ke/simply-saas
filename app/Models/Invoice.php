<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'subscription_id',
        'user_id',
        'invoice_number',
        'status',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'currency',
        'due_date',
        'paid_at',
        'stripe_invoice_id',
        'billing_address',
        'line_items',
        'notes',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'due_date' => 'date',
        'paid_at' => 'date',
        'billing_address' => 'array',
        'line_items' => 'array',
    ];

    /**
     * Get the subscription that owns the invoice
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * Get the user that owns the invoice
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the payments for this invoice
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Check if invoice is paid
     */
    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    /**
     * Check if invoice is overdue
     */
    public function isOverdue(): bool
    {
        return $this->status !== 'paid' && $this->due_date->isPast();
    }

    /**
     * Check if invoice is draft
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Check if invoice is sent
     */
    public function isSent(): bool
    {
        return $this->status === 'sent';
    }

    /**
     * Check if invoice is canceled
     */
    public function isCanceled(): bool
    {
        return $this->status === 'canceled';
    }

    /**
     * Get invoice amount for display
     */
    public function getDisplayAmount(): string
    {
        return '$' . number_format($this->total_amount, 2);
    }

    /**
     * Get invoice subtotal for display
     */
    public function getDisplaySubtotal(): string
    {
        return '$' . number_format($this->subtotal, 2);
    }

    /**
     * Get invoice tax amount for display
     */
    public function getDisplayTaxAmount(): string
    {
        return '$' . number_format($this->tax_amount, 2);
    }

    /**
     * Get invoice discount amount for display
     */
    public function getDisplayDiscountAmount(): string
    {
        return '$' . number_format($this->discount_amount, 2);
    }

    /**
     * Get days until due
     */
    public function getDaysUntilDue(): int
    {
        return now()->diffInDays($this->due_date, false);
    }

    /**
     * Get days overdue
     */
    public function getDaysOverdue(): int
    {
        if (!$this->isOverdue()) {
            return 0;
        }

        return now()->diffInDays($this->due_date);
    }

    /**
     * Mark invoice as paid
     */
    public function markAsPaid(): void
    {
        $this->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);
    }

    /**
     * Mark invoice as sent
     */
    public function markAsSent(): void
    {
        $this->update([
            'status' => 'sent',
        ]);
    }

    /**
     * Cancel invoice
     */
    public function cancel(): void
    {
        $this->update([
            'status' => 'canceled',
        ]);
    }

    /**
     * Get invoice status for display
     */
    public function getStatusDisplay(): string
    {
        return match($this->status) {
            'draft' => 'Draft',
            'sent' => 'Sent',
            'paid' => 'Paid',
            'overdue' => 'Overdue',
            'canceled' => 'Canceled',
            default => ucfirst($this->status),
        };
    }

    /**
     * Get invoice status color
     */
    public function getStatusColor(): string
    {
        return match($this->status) {
            'draft' => 'secondary',
            'sent' => 'info',
            'paid' => 'success',
            'overdue' => 'danger',
            'canceled' => 'danger',
            default => 'secondary',
        };
    }

    /**
     * Calculate total amount
     */
    public function calculateTotal(): float
    {
        return $this->subtotal + $this->tax_amount - $this->discount_amount;
    }

    /**
     * Get invoice line items
     */
    public function getLineItems(): array
    {
        return $this->line_items ?? [];
    }

    /**
     * Add line item to invoice
     */
    public function addLineItem(array $item): void
    {
        $lineItems = $this->line_items ?? [];
        $lineItems[] = $item;
        
        $this->update(['line_items' => $lineItems]);
    }

    /**
     * Get billing address
     */
    public function getBillingAddress(): array
    {
        return $this->billing_address ?? [];
    }

    /**
     * Set billing address
     */
    public function setBillingAddress(array $address): void
    {
        $this->update(['billing_address' => $address]);
    }

    /**
     * Generate invoice number
     */
    public function generateInvoiceNumber(): string
    {
        $prefix = 'INV';
        $year = now()->format('Y');
        $month = now()->format('m');
        $sequence = static::whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count() + 1;
        
        return sprintf('%s-%s%s-%04d', $prefix, $year, $month, $sequence);
    }

    /**
     * Scope for paid invoices
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    /**
     * Scope for unpaid invoices
     */
    public function scopeUnpaid($query)
    {
        return $query->where('status', '!=', 'paid');
    }

    /**
     * Scope for overdue invoices
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', '!=', 'paid')
            ->where('due_date', '<', now());
    }

    /**
     * Scope for draft invoices
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Get invoice by number
     */
    public static function findByNumber(string $number): ?self
    {
        return static::where('invoice_number', $number)->first();
    }

    /**
     * Get invoice by Stripe ID
     */
    public static function findByStripeId(string $stripeId): ?self
    {
        return static::where('stripe_invoice_id', $stripeId)->first();
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Generate invoice number if not provided
        static::creating(function ($invoice) {
            if (empty($invoice->invoice_number)) {
                $invoice->invoice_number = $invoice->generateInvoiceNumber();
            }
        });
    }
}
