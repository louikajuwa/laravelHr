<?php

namespace App\Services;

use App\Models\Payroll;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PayrollExportService
{
    public function payslipPdf(Payroll $payroll): Response
    {
        $payroll->load(['user.department', 'items', 'generatedBy']);

        $pdf = Pdf::loadView('hr.payroll.payslip_pdf', ['payroll' => $payroll])
            ->setPaper('a4');

        $name = 'payslip-'.$payroll->user->employee_code.'-'.$payroll->period_start->format('Y-m').'.pdf';

        return $pdf->download($name);
    }

    /**
     * @param  Collection<int,Payroll>  $payrolls
     */
    public function salaryReportCsv($payrolls, string $filename = 'salary-report.csv'): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->streamDownload(function () use ($payrolls) {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'Employee Code', 'Name', 'Period', 'Basic', 'Overtime', 'Bonuses',
                'Allowances', 'Gross', 'Late Ded.', 'Absence Ded.', 'Unpaid Leave Ded.',
                'Total Ded.', 'Net Pay', 'Status',
            ]);

            foreach ($payrolls as $p) {
                fputcsv($out, array_map($this->safeCell(...), [
                    $p->user?->employee_code,
                    $p->user?->name ?? '(deleted)',
                    $p->period_start->format('Y-m-d').' to '.$p->period_end->format('Y-m-d'),
                    $p->basic, $p->overtime_pay, $p->bonuses, $p->allowances_total, $p->gross,
                    $p->late_deduction, $p->absence_deduction, $p->unpaid_leave_deduction,
                    $p->deductions_total, $p->net_pay, $p->status,
                ]));
            }
            fclose($out);
        }, $filename, $headers);
    }

    /** Neutralize CSV formula injection in string cells. */
    public function safeCell(mixed $value): mixed
    {
        if (is_string($value) && $value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'".$value;
        }

        return $value;
    }
}
