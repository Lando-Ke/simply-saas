<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Models\Tenant;
use App\Models\Subscription;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class AdminStatsOverview extends BaseWidget
{
    public function getStats(): array
    {
        /** @var User|null $user */
        $user = Auth::user();
        
        if (!$user) {
            return [];
        }
        
        if ($user->hasRole(['super-admin', 'app-admin'])) {
            return $this->getAppAdminStats();
        } elseif ($user->hasRole('tenant-admin')) {
            return $this->getTenantAdminStats();
        }
        
        return [];
    }
    
    private function getAppAdminStats(): array
    {
        return [
            Stat::make('Total Tenants', Tenant::count())
                ->description('Registered tenants')
                ->descriptionIcon('heroicon-m-building-office')
                ->color('primary'),
                
            Stat::make('Total Users', User::count())
                ->description('All registered users')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),
                
            Stat::make('Active Subscriptions', Subscription::where('status', 'active')->count())
                ->description('Currently active subscriptions')
                ->descriptionIcon('heroicon-m-credit-card')
                ->color('success'),
                
            Stat::make('Revenue This Month', '$' . number_format(
                Subscription::where('status', 'active')
                    ->whereMonth('created_at', now()->month)
                    ->sum('amount'), 2
            ))
                ->description('Monthly recurring revenue')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('info'),
        ];
    }
    
    private function getTenantAdminStats(): array
    {
        $currentTenant = tenant();
        
        if (!$currentTenant) {
            return [
                Stat::make('No Tenant Selected', 0)
                    ->description('Please select a tenant')
                    ->descriptionIcon('heroicon-m-exclamation-triangle')
                    ->color('warning'),
            ];
        }
        
        $tenantUserIds = $currentTenant->users()->pluck('users.id');
        
        return [
            Stat::make('Tenant Users', $tenantUserIds->count())
                ->description('Users in your organization')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),
                
            Stat::make('Active Subscriptions', 
                Subscription::whereIn('user_id', $tenantUserIds)
                    ->where('status', 'active')
                    ->count()
            )
                ->description('Active subscriptions')
                ->descriptionIcon('heroicon-m-credit-card')
                ->color('success'),
                
            Stat::make('Monthly Spend', '$' . number_format(
                Subscription::whereIn('user_id', $tenantUserIds)
                    ->where('status', 'active')
                    ->sum('amount'), 2
            ))
                ->description('Monthly subscription costs')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('info'),
                
            Stat::make('Tenant Status', $currentTenant->isActive() ? 'Active' : 'Inactive')
                ->description('Current tenant status')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color($currentTenant->isActive() ? 'success' : 'danger'),
        ];
    }
}
