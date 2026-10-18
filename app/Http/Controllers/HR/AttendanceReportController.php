<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Services\AttendanceReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class AttendanceReportController extends Controller
{
    public function __construct(private AttendanceReportService $reports) {}

    public function index(Request $request, string $period = 'daily'): View
    {
        [$from, $to, $label] = $this->resolvePeriod($period, $request);
        $deptId = $request->filled('department_id') ? $request->integer('department_id') : null;

        return view('hr.attendance.reports', [
            'period' => $period,
            'label' => $label,
            'from' => $from,
            'to' => $to,
            'summary' => $this->reports->summary($from, $to, $deptId),
            'records' => $this->reports->detail($from, $to, $deptId, $request->query('status'), 15),
            'departments' => Department::orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * @return array{0:string,1:string,2:string}
     */
    private function resolvePeriod(string $period, Request $request): array
    {
        $anchor = $request->date('date') ?? now();

        return match ($period) {
            'weekly' => [
                $anchor->copy()->startOfWeek()->toDateString(),
                $anchor->copy()->endOfWeek()->toDateString(),
                'Week of '.$anchor->copy()->startOfWeek()->format('M d, Y'),
            ],
            'monthly' => [
                $anchor->copy()->startOfMonth()->toDateString(),
                $anchor->copy()->endOfMonth()->toDateString(),
                $anchor->format('F Y'),
            ],
            default => [
                $anchor->toDateString(),
                $anchor->toDateString(),
                $anchor->format('M d, Y'),
            ],
        };
    }
}
