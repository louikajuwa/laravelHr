<?php

namespace App\Http\Controllers\Me;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\Payroll;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $today = Attendance::forUser($user->id)->where('date', now()->toDateString())->first();

        $monthStart = now()->startOfMonth()->toDateString();
        $monthAttendance = Attendance::forUser($user->id)->between($monthStart, now()->toDateString())->get();

        return view('me.dashboard.index', [
            'user' => $user->load(['department:id,name', 'jobPosition:id,title', 'shift']),
            'today' => $today,
            'monthStats' => [
                'present' => $monthAttendance->whereIn('status', ['present', 'late'])->count(),
                'late' => $monthAttendance->where('is_late', true)->count(),
                'late_minutes' => (int) $monthAttendance->sum('late_minutes'),
                'overtime_minutes' => (int) $monthAttendance->sum('overtime_minutes'),
            ],
            'balances' => $user->leaveBalances()->with('leaveType:id,name,code')->where('year', now()->year)->get(),
            'recentLeave' => LeaveRequest::forUser($user->id)->with('leaveType:id,name,code')->latest()->take(5)->get(),
            'latestPayroll' => Payroll::where('user_id', $user->id)->whereIn('status', ['finalized', 'paid'])->orderByDesc('period_start')->first(),
        ]);
    }
}
