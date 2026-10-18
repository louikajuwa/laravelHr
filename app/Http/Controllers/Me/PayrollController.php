<?php

namespace App\Http\Controllers\Me;

use App\Http\Controllers\Controller;
use App\Models\Payroll;
use App\Services\PayrollExportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PayrollController extends Controller
{
    public function __construct(private PayrollExportService $export) {}

    public function index(Request $request): View|JsonResponse
    {
        $payrolls = Payroll::where('user_id', $request->user()->id)
            ->whereIn('status', ['finalized', 'paid'])
            ->orderByDesc('period_start')->paginate(12)->withQueryString();

        if ($request->expectsJson()) {
            return response()->json([
                'data' => $payrolls->getCollection()->map(fn ($p) => [
                    'id' => $p->id,
                    'label' => $p->label,
                    'period_start_epoch' => $p->period_start->timestamp,
                    'period_end_epoch' => $p->period_end->timestamp,
                    'gross' => number_format($p->gross, 2),
                    'deductions_total' => number_format($p->deductions_total, 2),
                    'net_pay' => number_format($p->net_pay, 2),
                    'status' => $p->status,
                    'urls' => [
                        'show' => route('me.payroll.show', $p),
                        'payslip' => route('me.payroll.payslip', $p),
                    ],
                ]),
                'pagination' => $this->paginationMeta($payrolls),
            ]);
        }

        return view('me.payroll.index', ['payrolls' => $payrolls]);
    }

    public function show(Request $request, Payroll $payroll): View
    {
        abort_unless($this->visible($request, $payroll), 404);
        $payroll->load('items');

        return view('me.payroll.show', ['payroll' => $payroll]);
    }

    public function payslip(Request $request, Payroll $payroll): Response
    {
        abort_unless($this->visible($request, $payroll), 404);

        return $this->export->payslipPdf($payroll);
    }

    private function visible(Request $request, Payroll $payroll): bool
    {
        return $payroll->user_id === $request->user()->id
            && in_array($payroll->status, ['finalized', 'paid'], true);
    }
}
