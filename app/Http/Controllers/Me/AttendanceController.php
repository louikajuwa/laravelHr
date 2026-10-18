<?php

namespace App\Http\Controllers\Me;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Services\AttendanceService;
use App\Support\BusinessTime;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class AttendanceController extends Controller
{
    public function __construct(private AttendanceService $attendance) {}

    public function index(Request $request): View|JsonResponse
    {
        $month = $request->query('month');
        $month = is_string($month) && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month) ? $month : BusinessTime::today()->format('Y-m');
        $start = Carbon::parse($month.'-01')->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $records = Attendance::forUser($request->user()->id)
            ->between($start->toDateString(), $end->toDateString())
            ->orderByDesc('date')->paginate(15)->withQueryString();

        if ($request->expectsJson()) {
            return response()->json([
                'data' => $records->getCollection()->map(fn ($r) => [
                    'id' => $r->id,
                    'date_epoch' => $r->date->timestamp,
                    'time_in_epoch' => $r->time_in ? $r->epoch('time_in') : null,
                    'time_out_epoch' => $r->time_out ? $r->epoch('time_out') : null,
                    'status' => $r->status,
                    'late_minutes' => $r->late_minutes,
                    'overtime_minutes' => $r->overtime_minutes,
                ]),
                'pagination' => $this->paginationMeta($records),
            ]);
        }

        return view('me.attendance.index', [
            'records' => $records,
            'month' => $month,
        ]);
    }

    public function clockIn(Request $request): JsonResponse
    {
        try {
            $att = $this->attendance->clockIn($request->user());

            return response()->json([
                'ok' => true,
                'message' => $att->is_late ? "Clocked in (late by {$att->late_minutes} min)." : 'Clocked in.',
                'time_in_epoch' => $att->epoch('time_in'),
                'is_late' => $att->is_late,
            ]);
        } catch (RuntimeException $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function clockOut(Request $request): JsonResponse
    {
        try {
            $att = $this->attendance->clockOut($request->user());

            return response()->json([
                'ok' => true,
                'message' => 'Clocked out.',
                'time_out_epoch' => $att->epoch('time_out'),
                'worked_minutes' => $att->worked_minutes,
                'overtime_minutes' => $att->overtime_minutes,
            ]);
        } catch (RuntimeException $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }
}
