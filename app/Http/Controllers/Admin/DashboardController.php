<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboardService)
    {
    }

    public function index(Request $request): View
    {
        $periodType = $request->query('period_type');
        $periodValue = $request->query('period_value');

        if (! in_array($periodType, ['day', 'month', 'year'], true)) {
            $periodType = null;
            $periodValue = null;
        }

        $dashboard = $this->dashboardService->getAdminDashboardData($periodType, $periodValue);
        $dashboard['filters'] = [
            'period_type' => $periodType,
            'period_value' => $periodValue,
        ];

        return view('admin.dashboard', $dashboard);
    }
}
