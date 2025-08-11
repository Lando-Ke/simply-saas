<?php

namespace App\Filament\Widgets;

use App\Models\Subscription;
use App\Models\User;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class RevenueChart extends ChartWidget
{
    protected static ?string $heading = 'Revenue & User Growth';

    protected static ?int $sort = 4;

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
        
        // Apply date filter
        $startDate = match ($this->filter) {
            '7days' => now()->subDays(7),
            '30days' => now()->subDays(30),
            '3months' => now()->subMonths(3),
            '6months' => now()->subMonths(6),
            '1year' => now()->subYear(),
            default => now()->subMonths(6),
        };

        if ($user->hasRole(['super-admin', 'app-admin'])) {
            return $this->getAppAdminData($startDate);
        } elseif ($user->hasRole('tenant-admin')) {
            return $this->getTenantAdminData($startDate);
        }

        return [
            'datasets' => [],
            'labels' => [],
        ];
    }

    private function getAppAdminData(Carbon $startDate): array
    {
        // Get revenue data
        $revenueData = Subscription::where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, SUM(amount) as revenue')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Get user growth data
        $userGrowthData = User::where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as new_users')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return $this->formatChartData($startDate, $revenueData, $userGrowthData, 'revenue');
    }

    private function getTenantAdminData(Carbon $startDate): array
    {
        $currentTenant = tenant();
        
        if (!$currentTenant) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        $tenantUserIds = $currentTenant->users()->pluck('users.id');

        // Get tenant spending data
        $spendingData = Subscription::whereIn('user_id', $tenantUserIds)
            ->where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, SUM(amount) as spending')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Get tenant user growth
        $userGrowthData = $currentTenant->users()
            ->wherePivot('joined_at', '>=', $startDate)
            ->selectRaw('DATE(tenant_users.joined_at) as date, COUNT(*) as new_users')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return $this->formatChartData($startDate, $spendingData, $userGrowthData, 'spending');
    }

    private function formatChartData(Carbon $startDate, $primaryData, $userGrowthData, string $primaryLabel): array
    {
        $period = $this->getDatePeriod($startDate, now());
        $labels = [];
        $primaryValues = [];
        $userValues = [];

        foreach ($period as $date) {
            $dateString = $date->format('Y-m-d');
            $labels[] = $date->format('M j');
            
            $primaryValue = $primaryData
                ->where('date', $dateString)
                ->sum($primaryLabel);
            
            $userCount = $userGrowthData
                ->where('date', $dateString)
                ->sum('new_users');
                
            $primaryValues[] = $primaryValue;
            $userValues[] = $userCount;
        }

        $primaryColor = $primaryLabel === 'revenue' ? 'rgb(34, 197, 94)' : 'rgb(59, 130, 246)';
        $primaryBgColor = $primaryLabel === 'revenue' ? 'rgba(34, 197, 94, 0.1)' : 'rgba(59, 130, 246, 0.1)';

        return [
            'datasets' => [
                [
                    'label' => ucfirst($primaryLabel) . ' ($)',
                    'data' => $primaryValues,
                    'backgroundColor' => $primaryBgColor,
                    'borderColor' => $primaryColor,
                    'borderWidth' => 2,
                    'tension' => 0.1,
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'New Users',
                    'data' => $userValues,
                    'backgroundColor' => 'rgba(168, 85, 247, 0.1)',
                    'borderColor' => 'rgb(168, 85, 247)',
                    'borderWidth' => 2,
                    'tension' => 0.1,
                    'yAxisID' => 'y1',
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
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'left',
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 2,
                        'callback' => "function(value) { return '$' + value.toFixed(2); }",
                    ],
                ],
                'y1' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'right',
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
                    ],
                    'grid' => [
                        'drawOnChartArea' => false,
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
