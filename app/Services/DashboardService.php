<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\DashboardRepository;

class DashboardService
{
    public function __construct(
        private readonly DashboardRepository $dashboardRepository,
    ) {
    }

    public function buildDashboardData(?User $user): array
    {
        $tenantId = $user?->tenant_id;

        $tenant = $this->dashboardRepository->findTenantOverview($tenantId);
        $summary = $this->dashboardRepository->buildSummaryMetrics($tenantId);
        $recentWorkflows = $this->dashboardRepository->recentWorkflows($tenantId);
        $recentRuns = $this->dashboardRepository->recentRuns($tenantId);
        $recentLogs = $this->dashboardRepository->recentLogs($tenantId);
        $failureHotspots = $this->dashboardRepository->failureHotspots($tenantId);

        return compact(
            'tenant',
            'summary',
            'recentWorkflows',
            'recentRuns',
            'recentLogs',
            'failureHotspots'
        );
    }
}
