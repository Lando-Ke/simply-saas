<?php

namespace App\Filament\Widgets;

use App\Models\Subscription;
use Filament\Widgets\ChartWidget;
use Filament\Forms;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class SubscriptionTrendsChart extends ChartWidget
{
    protected static ?string $heading = 'Subscription Trends';

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    public ?string $filter = '6months';

    protected function getFilters(): ?array
    {
        return [
            '7days' => 'Last 7 days',
            '30days' => 'Last 30 days',
            '3months' => 'Last 3 months',
            '6months' => 'Last 6 months',
            '1year' => 'Last year',
        ];
    }

    public function getData(): array
    {
        /** @var User|null $user */
        $user = Auth::user();
        
        if (!$user) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }
        $query = Subscription::query();

        // Apply role-based filtering
        if ($user->hasRole('tenant-admin') && !$user->hasRole(['super-admin', 'app-admin'])) {
            $currentTenant = tenant();
            if ($currentTenant) {
                $tenantUserIds = $currentTenant->users()->pluck('users.id');
                $query->whereIn('user_id', $tenantUserIds);
            } else {
                return [
                    'datasets' => [],
                    'labels' => [],
                ];
            }
        }

        // Apply date filter
        $startDate = match ($this->filter) {
            '7days' => now()->subDays(7),
            '30days' => now()->subDays(30),
            '3months' => now()->subMonths(3),
            '6months' => now()->subMonths(6),
            '1year' => now()->subYear(),
            default => now()->subMonths(6),
        };

        $subscriptions = $query->where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count, status')
            ->groupBy('date', 'status')
            ->orderBy('date')
            ->get();

        // Get all unique dates in the range
        $period = $this->getDatePeriod($startDate, now());
        $labels = [];
        $activeData = [];
        $canceledData = [];

        foreach ($period as $date) {
            $dateString = $date->format('Y-m-d');
            $labels[] = $date->format('M j');
            
            $activeCount = $subscriptions
                ->where('date', $dateString)
                ->where('status', 'active')
                ->sum('count');
            
            $canceledCount = $subscriptions
                ->where('date', $dateString)
                ->where('status', 'canceled')
                ->sum('count');
                
            $activeData[] = $activeCount;
            $canceledData[] = $canceledCount;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Active Subscriptions',
                    'data' => $activeData,
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'borderColor' => 'rgb(34, 197, 94)',
                    'borderWidth' => 2,
                    'tension' => 0.1,
                ],
                [
                    'label' => 'Canceled Subscriptions',
                    'data' => $canceledData,
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                    'borderColor' => 'rgb(239, 68, 68)',
                    'borderWidth' => 2,
                    'tension' => 0.1,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
                    ],
                ],
            ],
        ];
    }

    private function getDatePeriod(Carbon $start, Carbon $end): \DatePeriod
    {
        return new \DatePeriod(
            $start,
            new \DateInterval('P1D'),
            $end->addDay()
        );
    }
}
