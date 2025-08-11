<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Str;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'billing_cycle',
        'features',
        'max_users',
        'max_storage_gb',
        'max_api_calls',
        'is_active',
        'is_featured',
        'sort_order',
    ];

    protected $casts = [
        'features' => 'array',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'price' => 'decimal:2',
    ];

    /**
     * Get the subscriptions for this plan
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Get the active subscriptions for this plan
     */
    public function activeSubscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class)->where('status', 'active');
    }

    /**
     * Check if plan is available for subscription
     */
    public function isAvailable(): bool
    {
        return $this->is_active;
    }

    /**
     * Get plan price in cents for Stripe
     */
    public function getPriceInCents(): int
    {
        return (int) ($this->price * 100);
    }

    /**
     * Get plan price for display
     */
    public function getDisplayPrice(): string
    {
        return '$' . number_format($this->price, 2);
    }

    /**
     * Check if plan has specific feature
     */
    public function hasFeature(string $feature): bool
    {
        return in_array($feature, $this->features ?? []);
    }

    /**
     * Get plan features as array
     */
    public function getFeatures(): array
    {
        return $this->features ?? [];
    }

    /**
     * Check if plan is free
     */
    public function isFree(): bool
    {
        return $this->price == 0;
    }

    /**
     * Check if plan is premium
     */
    public function isPremium(): bool
    {
        return $this->price > 0;
    }

    /**
     * Get yearly price (if monthly)
     */
    public function getYearlyPrice(): float
    {
        if ($this->billing_cycle === 'yearly') {
            return $this->price;
        }
        
        return $this->price * 12;
    }

    /**
     * Get monthly price (if yearly)
     */
    public function getMonthlyPrice(): float
    {
        if ($this->billing_cycle === 'monthly') {
            return $this->price;
        }
        
        return $this->price / 12;
    }

    /**
     * Get plan name with price
     */
    public function getFullName(): string
    {
        return $this->name . ' - ' . $this->getDisplayPrice() . '/' . $this->billing_cycle;
    }

    /**
     * Scope for active plans
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for featured plans
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope for free plans
     */
    public function scopeFree($query)
    {
        return $query->where('price', 0);
    }

    /**
     * Scope for paid plans
     */
    public function scopePaid($query)
    {
        return $query->where('price', '>', 0);
    }

    /**
     * Scope for monthly plans
     */
    public function scopeMonthly($query)
    {
        return $query->where('billing_cycle', 'monthly');
    }

    /**
     * Scope for yearly plans
     */
    public function scopeYearly($query)
    {
        return $query->where('billing_cycle', 'yearly');
    }

    /**
     * Get plan by slug
     */
    public static function findBySlug(string $slug): ?self
    {
        return static::where('slug', $slug)->first();
    }

    /**
     * Get default plan
     */
    public static function getDefault(): ?self
    {
        return static::active()->orderBy('sort_order')->first();
    }

    /**
     * Get featured plans
     */
    public static function getFeatured(): \Illuminate\Database\Eloquent\Collection
    {
        return static::active()->featured()->orderBy('sort_order')->get();
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Generate slug from name if not provided
        static::creating(function ($plan) {
            if (empty($plan->slug)) {
                $plan->slug = Str::slug($plan->name);
            }
        });
    }
}
