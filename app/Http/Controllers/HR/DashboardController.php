<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private AnalyticsService $analytics) {}

    public function index(): View
    {
        return view('hr.dashboard.index', [
            'headcount' => $this->analytics->headcount(),
            'today' => $this->analytics->todayAttendance(),
            'leave' => $this->analytics->leaveSummary(),
            'payroll' => $this->analytics->payrollOverview(),
            'queue' => $this->analytics->pendingQueue(),
        ]);
    }

    public function metrics(): JsonResponse
    {
        return response()->json([
            'today' => $this->analytics->todayAttendance(),
            'trend' => $this->analytics->attendanceTrend(30),
            'leave_by_type' => $this->analytics->leaveSummary()['by_type'],
        ]);
    }
}
