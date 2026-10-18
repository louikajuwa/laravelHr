<?php

namespace App\Http\Controllers\Me;

use App\Http\Controllers\Controller;
use App\Http\Requests\Leave\StoreLeaveRequest;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Services\LeaveService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class LeaveController extends Controller
{
    public function __construct(private LeaveService $leaves) {}

    public function index(Request $request): View|\Illuminate\Http\JsonResponse
    {
        $requests = LeaveRequest::forUser($request->user()->id)
            ->with('leaveType:id,name,code')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->orderByDesc('created_at')->paginate(15)->withQueryString();

        if ($request->expectsJson()) {
            return response()->json([
                'data' => $requests->getCollection()->map(fn ($r) => [
                    'id'               => $r->id,
                    'leave_type'       => $r->leaveType->name,
                    'reason'           => $r->reason,
                    'start_date_epoch' => $r->start_date->timestamp,
                    'end_date_epoch'   => $r->end_date->timestamp,
                    'days'             => $r->days,
                    'status'           => $r->status,
                    'urls'             => ['cancel' => route('me.leave.cancel', $r)],
                ]),
                'pagination' => $this->paginationMeta($requests),
            ]);
        }

        return view('me.leave.index', ['requests' => $requests]);
    }

    public function create(Request $request): View
    {
        return view('me.leave.create', [
            'types' => LeaveType::where('is_active', true)->orderBy('name')->get(),
            'balances' => $request->user()->leaveBalances()->where('year', now()->year)->get()->keyBy('leave_type_id'),
        ]);
    }

    public function store(StoreLeaveRequest $request): RedirectResponse
    {
        try {
            $this->leaves->request($request->user(), $request->validated());

            return redirect()->route('me.leave.index')->with('status', 'Leave request submitted.');
        } catch (RuntimeException $e) {
            return back()->withInput()->withErrors(['leave' => $e->getMessage()]);
        }
    }

    public function cancel(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        abort_unless($leaveRequest->user_id === $request->user()->id, 403);

        try {
            $this->leaves->cancel($leaveRequest);

            return back()->with('status', 'Leave request cancelled.');
        } catch (RuntimeException $e) {
            return back()->withErrors(['leave' => $e->getMessage()]);
        }
    }

    public function balances(Request $request): View
    {
        return view('me.leave.balances', [
            'balances' => $request->user()->leaveBalances()->with('leaveType:id,name,code')->where('year', now()->year)->get(),
        ]);
    }

    public function balanceLookup(Request $request): JsonResponse
    {
        $balance = $request->user()->leaveBalances()
            ->where('leave_type_id', $request->integer('leave_type_id'))
            ->where('year', now()->year)->first();

        return response()->json([
            'available' => $balance?->available ?? 0,
            'allocated' => $balance?->allocated ?? 0,
        ]);
    }
}
