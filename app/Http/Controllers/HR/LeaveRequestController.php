<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Services\LeaveService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class LeaveRequestController extends Controller
{
    public function __construct(private LeaveService $leaves) {}

    public function index(Request $request): View|JsonResponse
    {
        $requests = LeaveRequest::query()
            ->with(['user:id,name,employee_code,department_id', 'user.department:id,name', 'leaveType:id,name,code'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->when($request->filled('leave_type_id'), fn ($q) => $q->where('leave_type_id', $request->integer('leave_type_id')))
            ->when($request->filled('q'), fn ($q) => $q->whereHas('user', fn ($u) => $u->search($request->query('q'))))
            ->orderByDesc('created_at')
            ->paginate(15)->withQueryString();

        if ($request->expectsJson()) {
            return response()->json([
                'data' => $requests->getCollection()->map(fn ($r) => [
                    'id' => $r->id,
                    'user_name' => $r->user->name,
                    'employee_code' => $r->user->employee_code,
                    'department' => $r->user->department?->name,
                    'leave_type' => $r->leaveType->name,
                    'start_date_epoch' => $r->start_date->timestamp,
                    'end_date_epoch' => $r->end_date->timestamp,
                    'days' => $r->days,
                    'status' => $r->status,
                    'decision_note' => $r->decision_note,
                    'urls' => [
                        'approve' => route('hr.leave.approve', $r),
                        'reject' => route('hr.leave.reject', $r),
                        'revoke' => $r->status === 'approved' ? route('hr.leave.revoke', $r) : null,
                    ],
                ]),
                'pagination' => $this->paginationMeta($requests),
            ]);
        }

        return view('hr.leave.index', [
            'requests' => $requests,
            'types' => LeaveType::orderBy('name')->get(['id', 'name']),
        ]);
    }

    private function decisionNote(Request $request, LeaveRequest $leaveRequest): ?string
    {
        if ($leaveRequest->user_id === $request->user()->id) {
            throw new RuntimeException('You cannot decide your own leave request.');
        }

        return $request->validate(['decision_note' => ['nullable', 'string', 'max:1000']])['decision_note'] ?? null;
    }

    public function approve(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        try {
            $note = $this->decisionNote($request, $leaveRequest);
            $this->leaves->approve($leaveRequest, $request->user(), $note);

            return back()->with('status', 'Leave approved.');
        } catch (RuntimeException $e) {
            return back()->withErrors(['leave' => $e->getMessage()]);
        }
    }

    public function reject(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        try {
            $note = $this->decisionNote($request, $leaveRequest);
            $this->leaves->reject($leaveRequest, $request->user(), $note);

            return back()->with('status', 'Leave rejected.');
        } catch (RuntimeException $e) {
            return back()->withErrors(['leave' => $e->getMessage()]);
        }
    }

    public function revoke(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        try {
            if ($leaveRequest->user_id === $request->user()->id) {
                throw new RuntimeException('You cannot revoke your own leave request.');
            }
            $note = $request->validate(['note' => ['nullable', 'string', 'max:1000']])['note'] ?? null;
            [, $discarded] = $this->leaves->revoke($leaveRequest, $request->user(), $note);

            $msg = 'Leave revoked and balance refunded.';
            if ($discarded > 0) {
                $msg .= " {$discarded} draft payroll(s) covering these dates were discarded; please regenerate them.";
            }

            return back()->with('status', $msg);
        } catch (RuntimeException $e) {
            return back()->withErrors(['leave' => $e->getMessage()]);
        }
    }
}
