<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\LateArrivalReport;
use App\Services\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LateArrivalController extends Controller
{
    public function __construct(private AuditLogger $audit) {}

    public function index(Request $request): View|\Illuminate\Http\JsonResponse
    {
        $reports = LateArrivalReport::query()
            ->with(['user:id,name,employee_code', 'reviewer:id,name'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->orderByDesc('date')->paginate(15)->withQueryString();

        if ($request->expectsJson()) {
            return response()->json([
                'data' => $reports->getCollection()->map(fn ($r) => [
                    'id'            => $r->id,
                    'date_epoch'    => $r->date->timestamp,
                    'user_name'     => $r->user->name,
                    'employee_code' => $r->user->employee_code,
                    'late_minutes'  => $r->late_minutes,
                    'reason'        => $r->reason,
                    'status'        => $r->status,
                    'reviewer'      => $r->reviewer?->name,
                    'urls'          => ['review' => route('hr.late-arrivals.review', $r)],
                ]),
                'pagination' => $this->paginationMeta($reports),
            ]);
        }

        return view('hr.late_arrivals.index', ['reports' => $reports]);
    }

    public function review(Request $request, LateArrivalReport $report): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:accepted,excused,rejected'],
        ]);

        $report->update([
            'status' => $data['status'],
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        $this->audit->log('late_arrival.reviewed', $report, [], ['status' => $data['status']],
            "Reviewed late report #{$report->id} as {$data['status']}");

        return back()->with('status', 'Late arrival report reviewed.');
    }
}
