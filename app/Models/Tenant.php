<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasTenants;
use Filament\Models\Contracts\HasName;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Filament\Panel;

class Tenant extends BaseTenant implements HasName
{
    use HasFactory, HasDomains;

    protected $fillable = [
        'id',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    /**
     * Get the users for this tenant (many-to-many relationship)
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tenant_users')
            ->withPivot(['role', 'joined_at'])
            ->withTimestamps();
    }

    /**
     * Check if tenant is active
     */
    public function isActive(): bool
    {
        return $this->data['status'] ?? 'active' === 'active';
    }

    /**
     * Check if tenant is on trial
     */
    public function isOnTrial(): bool
    {
        $trialEndsAt = $this->data['trial_ends_at'] ?? null;
        return $trialEndsAt && now()->lt($trialEndsAt);
    }

    /**
     * Check if tenant subscription is active
     */
    public function hasActiveSubscription(): bool
    {
        $subscriptionEndsAt = $this->data['subscription_ends_at'] ?? null;
        return $subscriptionEndsAt && now()->lt($subscriptionEndsAt);
    }

    /**
     * Get tenant's primary domain
     */
    public function getPrimaryDomain(): ?string
    {
        return $this->domains->first()?->domain;
    }

    /**
     * Get tenant's settings
     */
    public function getSetting(string $key, $default = null)
    {
        $settings = $this->data['settings'] ?? [];
        return data_get($settings, $key, $default);
    }

    /**
     * Set tenant's setting
     */
    public function setSetting(string $key, $value): void
    {
        $data = $this->data ?? [];
        $settings = $data['settings'] ?? [];
        data_set($settings, $key, $value);
        $data['settings'] = $settings;
        $this->update(['data' => $data]);
    }

    /**
     * Get tenant's subscription plan
     */
    public function getSubscriptionPlan(): string
    {
        return $this->data['subscription_plan'] ?? 'free';
    }

    /**
     * Check if tenant can access feature
     */
    public function canAccessFeature(string $feature): bool
    {
        $plan = $this->getSubscriptionPlan();
        
        $featureAccess = [
            'free' => ['basic_content'],
            'basic' => ['basic_content', 'advanced_content'],
            'premium' => ['basic_content', 'advanced_content', 'analytics'],
            'enterprise' => ['basic_content', 'advanced_content', 'analytics', 'custom_domain'],
        ];

        return in_array($feature, $featureAccess[$plan] ?? []);
    }

    /**
     * Get tenant's usage statistics
     */
    public function getUsageStats(): array
    {
        return [
            'users_count' => User::count(), // For now, count all users
            'storage_used' => 0, // TODO: Implement storage tracking
            'api_calls' => 0, // TODO: Implement API call tracking
        ];
    }

    /**
     * Get tenant name
     */
    public function getName(): string
    {
        return $this->data['name'] ?? 'Unknown Tenant';
    }

    /**
     * Get tenant email
     */
    public function getEmail(): ?string
    {
        return $this->data['email'] ?? null;
    }

    /**
     * Get the tenant's display name for Filament
     */
    public function getFilamentName(): string
    {
        return $this->getName();
    }

    /**
     * Get the tenant's avatar URL for Filament
     */
    public function getFilamentAvatarUrl(): ?string
    {
        return $this->getSetting('branding.logo');
    }






}
