<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use App\Models\Tenant;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class TenantSwitcher extends Widget
{
    protected static string $view = 'filament.widgets.tenant-switcher';

    protected int | string | array $columnSpan = 'full';

    /**
     * @return \Illuminate\Support\Collection<int, Tenant>
     */
    public function getTenants()
    {
        /** @var User|null $user */
        $user = Auth::user();
        
        if ($user?->hasRole('super-admin')) {
            return Tenant::with('domains')->get();
        }
        
        return $user?->tenants()->with('domains')->get() ?? collect();
    }

    public function getCurrentTenant()
    {
        return session('current_tenant_id') ? Tenant::find(session('current_tenant_id')) : null;
    }

    public function switchTenant($tenantId)
    {
        $tenant = Tenant::findOrFail($tenantId);
        
        // Check if user can access this tenant
        /** @var User|null $current */
        $current = Auth::user();
        if (!$current?->canAccessTenant($tenant)) {
            $this->dispatch('tenant-access-denied');
            return;
        }

        // Store current tenant in session
        session(['current_tenant_id' => $tenantId]);
        
        // Initialize tenant context
        tenancy()->initialize($tenant);
        
        $this->dispatch('tenant-switched', [
            'tenant' => $tenant->getName(),
            'tenantId' => $tenantId
        ]);
        
        // Refresh the page to apply tenant context
        return redirect(request()->header('Referer'));
    }

    public function clearTenant()
    {
        session()->forget('current_tenant_id');
        tenancy()->end();
        
        $this->dispatch('tenant-cleared');
        
        return redirect(request()->header('Referer'));
    }
}
