<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payroll\GeneratePayrollRequest;
use App\Models\Department;
use App\Models\Payroll;
use App\Services\PayrollExportService;
use App\Services\PayrollService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PayrollController extends Controller
{
    public function __construct(private PayrollService $payroll, private PayrollExportService $export) {}

    public function index(Request $request): View|JsonResponse
    {
        $request->validate(['period_start' => ['nullable', 'date'], 'status' => ['nullable', 'string'], 'q' => ['nullable', 'string']]);

        $payrolls = Payroll::query()
            ->with(['user:id,name,employee_code,department_id', 'user.department:id,name'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->when($request->filled('period_start'), fn ($q) => $q->where('period_start', $request->date('period_start')->toDateString()))
            ->when($request->filled('q'), fn ($q) => $q->whereHas('user', fn ($u) => $u->search($request->query('q'))))
            ->orderByDesc('period_start')->orderBy('user_id')
            ->paginate(15)->withQueryString();

        if ($request->expectsJson()) {
            return response()->json([
                'data' => $payrolls->getCollection()->map(fn ($p) => [
                    'id' => $p->id,
                    'user_name' => $p->user?->name,
                    'employee_code' => $p->user?->employee_code,
                    'department' => $p->user->department?->name,
                    'period_start_epoch' => $p->period_start->timestamp,
                    'period_end_epoch' => $p->period_end->timestamp,
                    'gross' => number_format($p->gross, 2),
                    'deductions_total' => number_format($p->deductions_total, 2),
                    'net_pay' => number_format($p->net_pay, 2),
                    'status' => $p->status,
                    'urls' => [
                        'show' => route('hr.payroll.show', $p),
                        'payslip' => route('hr.payroll.payslip', $p),
                    ],
                ]),
                'pagination' => $this->paginationMeta($payrolls),
            ]);
        }

        return view('hr.payroll.index', [
            'payrolls' => $payrolls,
            'departments' => Department::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function show(Payroll $payroll): View
    {
        $payroll->load(['user.department', 'items', 'generatedBy:id,name']);

        return view('hr.payroll.show', ['payroll' => $payroll]);
    }

    public function generate(GeneratePayrollRequest $request): RedirectResponse
    {
        $start = Carbon::parse($request->validated('period_start'));
        $end = Carbon::parse($request->validated('period_end'));

        $result = $this->payroll->generateBatch($start, $end, $request->user(), $request->input('department_id'));

        $msg = "Payroll: {$result['generated']} generated, {$result['skipped']} skipped.";
        if ($result['failed']) {
            return back()->with('status', $msg)->withErrors(['payroll' => implode(' | ', $result['failed'])]);
        }

        return back()->with('status', $msg);
    }

    public function finalize(Payroll $payroll): RedirectResponse
    {
        try {
            $this->payroll->finalize($payroll);
        } catch (RuntimeException $e) {
            return back()->withErrors(['payroll' => $e->getMessage()]);
        }

        return back()->with('status', 'Payroll finalized.');
    }

    public function markPaid(Payroll $payroll): RedirectResponse
    {
        try {
            $this->payroll->markPaid($payroll);
        } catch (RuntimeException $e) {
            return back()->withErrors(['payroll' => $e->getMessage()]);
        }

        return back()->with('status', 'Payroll marked as paid.');
    }

    public function payslip(Payroll $payroll): Response
    {
        return $this->export->payslipPdf($payroll);
    }

    public function export(Request $request): StreamedResponse
    {
        $request->validate(['period_start' => ['nullable', 'date'], 'status' => ['nullable', 'string']]);

        $payrolls = Payroll::query()->with('user:id,name,employee_code')
            ->when($request->filled('period_start'), fn ($q) => $q->where('period_start', $request->date('period_start')->toDateString()))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->orderByDesc('period_start')->get();

        return $this->export->salaryReportCsv($payrolls);
    }
}
