<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Http\Requests\Attendance\CorrectAttendanceRequest;
use App\Models\Attendance;
use App\Models\Department;
use App\Services\AttendanceService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function __construct(private AttendanceService $attendance) {}

    public function index(Request $request): View|JsonResponse
    {
        $from = $request->date('from')?->toDateString() ?? now()->startOfMonth()->toDateString();
        $to = $request->date('to')?->toDateString() ?? now()->toDateString();

        $records = Attendance::query()
            ->with(['user:id,name,employee_code,department_id', 'user.department:id,name'])
            ->between($from, $to)
            ->when($request->filled('department_id'), fn ($q) => $q->whereHas('user', fn ($u) => $u->where('department_id', $request->integer('department_id'))))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->when($request->filled('q'), fn ($q) => $q->whereHas('user', fn ($u) => $u->search($request->query('q'))))
            ->orderByDesc('date')->orderBy('user_id')
            ->paginate(15)->withQueryString();

        if ($request->expectsJson()) {
            return response()->json([
                'data' => $records->getCollection()->map(fn ($r) => [
                    'id' => $r->id,
                    'date_epoch' => $r->date->timestamp,
                    'user_name' => $r->user->name,
                    'employee_code' => $r->user->employee_code,
                    'department' => $r->user->department?->name,
                    'time_in_epoch' => $r->time_in ? $r->epoch('time_in') : null,
                    'time_out_epoch' => $r->time_out ? $r->epoch('time_out') : null,
                    'status' => $r->status,
                    'late_minutes' => $r->late_minutes,
                    'overtime_minutes' => $r->overtime_minutes,
                    'urls' => ['edit' => route('hr.attendance.edit', $r)],
                ]),
                'pagination' => $this->paginationMeta($records),
            ]);
        }

        return view('hr.attendance.index', [
            'records' => $records,
            'departments' => Department::orderBy('name')->get(['id', 'name']),
            'from' => $from,
            'to' => $to,
        ]);
    }

    public function edit(Attendance $attendance): View
    {
        $attendance->load(['user:id,name,employee_code', 'corrections.correctedBy:id,name', 'shift:id,name,start_time,end_time']);

        return view('hr.attendance.edit', ['attendance' => $attendance]);
    }

    public function update(CorrectAttendanceRequest $request, Attendance $attendance): RedirectResponse
    {
        $this->attendance->correct($attendance, [
            'time_in' => $request->input('time_in'),
            'time_out' => $request->input('time_out'),
            'status' => $request->input('status'),
            'remarks' => $request->input('remarks'),
        ], $request->user(), $request->input('reason'));

        return redirect()->route('hr.attendance.index')->with('status', 'Attendance corrected and logged.');
    }
}
