<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\Payroll;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    /**
     * @return array<string,mixed>
     */
    public function headcount(): array
    {
        return [
            'active' => User::active()->count(),
            'inactive' => User::inactive()->count(),
            'new_this_month' => User::whereBetween('hire_date', [
                now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString(),
            ])->count(),
            'by_department' => DB::table('users')
                ->leftJoin('departments', 'users.department_id', '=', 'departments.id')
                ->whereNull('users.deleted_at')
                ->selectRaw('COALESCE(departments.name, "Unassigned") as name, COUNT(*) as total')
                ->groupBy('name')->orderByDesc('total')->get(),
        ];
    }

    /**
     * @return array<string,int>
     */
    public function todayAttendance(): array
    {
        $today = now()->toDateString();
        $rows = Attendance::whereDate('date', $today)
            ->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');

        $present = (int) ($rows['present'] ?? 0);
        $late = (int) ($rows['late'] ?? 0);
        $onLeave = (int) ($rows['on_leave'] ?? 0);
        $active = User::active()->count();

        return [
            'present' => $present,
            'late' => $late,
            'on_leave' => $onLeave,
            'absent' => max(0, $active - $present - $late - $onLeave),
        ];
    }

    /**
     * @return array{labels:array<int,string>,present:array<int,int>,late:array<int,int>,absent:array<int,int>}
     */
    public function attendanceTrend(int $days = 30): array
    {
        $start = now()->subDays($days - 1)->startOfDay();
        $rows = Attendance::where('date', '>=', $start->toDateString())
            ->selectRaw('date, status, COUNT(*) as total')
            ->groupBy('date', 'status')->get()->groupBy(fn ($r) => Carbon::parse($r->date)->toDateString());

        $labels = $present = $late = $absent = [];
        for ($d = $start->copy(); $d->lessThanOrEqualTo(now()); $d->addDay()) {
            $key = $d->toDateString();
            $labels[] = $d->format('M d');
            $dayRows = $rows->get($key, collect());
            $present[] = (int) $dayRows->firstWhere('status', 'present')?->total;
            $late[] = (int) $dayRows->firstWhere('status', 'late')?->total;
            $absent[] = (int) $dayRows->firstWhere('status', 'absent')?->total;
        }

        return compact('labels', 'present', 'late', 'absent');
    }

    /**
     * @return array<string,mixed>
     */
    public function leaveSummary(): array
    {
        return [
            'pending' => LeaveRequest::pending()->count(),
            'approved_this_month' => LeaveRequest::where('status', 'approved')
                ->whereBetween('decided_at', [now()->startOfMonth(), now()->endOfMonth()])->count(),
            'by_type' => DB::table('leave_requests')
                ->join('leave_types', 'leave_requests.leave_type_id', '=', 'leave_types.id')
                ->where('leave_requests.status', 'approved')
                ->selectRaw('leave_types.name as name, COUNT(*) as total')
                ->groupBy('name')->get(),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public function payrollOverview(): array
    {
        $last = Payroll::orderByDesc('period_start')->value('period_start');
        $q = Payroll::query();
        if ($last) {
            $q->where('period_start', $last);
        }

        return [
            'period' => $last ? Carbon::parse($last)->format('M Y') : null,
            'total_net' => (float) (clone $q)->sum('net_pay'),
            'total_deductions' => (float) (clone $q)->sum('deductions_total'),
            'total_overtime' => (float) (clone $q)->sum('overtime_pay'),
            'headcount' => (clone $q)->count(),
        ];
    }

    /**
     * @return array<string,int>
     */
    public function pendingQueue(): array
    {
        return [
            'leave' => LeaveRequest::pending()->count(),
            'late_arrivals' => DB::table('late_arrival_reports')->where('status', 'pending')->count(),
        ];
    }
}
