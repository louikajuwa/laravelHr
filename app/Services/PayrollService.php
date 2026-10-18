<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\LateArrivalReport;
use App\Models\LeaveRequest;
use App\Models\Payroll;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PayrollService
{
    public function __construct(private AuditLogger $audit) {}

    public function generate(User $user, Carbon $start, Carbon $end, User $hr): Payroll
    {
        if (Payroll::where('user_id', $user->id)->forPeriod($start->toDateString(), $end->toDateString())->exists()) {
            throw new RuntimeException("Payroll for {$user->name} in this period already exists.");
        }

        $structure = $user->salaryStructures()->activeOn($start->toDateString())->first();
        if (! $structure) {
            throw new RuntimeException("No active salary structure for {$user->name}.");
        }

        $shift = $user->effectiveShift();
        $dailyRate = $structure->dailyRate();
        $minuteRate = $structure->minuteRate($shift);

        // --- Gather attendance ---
        $attendances = Attendance::forUser($user->id)
            ->between($start->toDateString(), $end->toDateString())
            ->get()->keyBy(fn ($a) => $a->date->toDateString());

        $excusedDates = LateArrivalReport::where('user_id', $user->id)
            ->where('status', 'excused')
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->pluck('date')->map(fn ($d) => Carbon::parse($d)->toDateString())->all();

        $daysPresent = $daysAbsent = $lateMinutes = $overtimeMinutes = 0;

        for ($d = $start->copy(); $d->lessThanOrEqualTo($end); $d->addDay()) {
            if ($shift && ! $shift->worksOn($d)) {
                continue;
            }
            $att = $attendances->get($d->toDateString());

            if ($att && in_array($att->status, ['present', 'late'], true)) {
                $daysPresent++;
                if (! in_array($d->toDateString(), $excusedDates, true)) {
                    $lateMinutes += (int) $att->late_minutes;
                }
                $overtimeMinutes += (int) $att->overtime_minutes;
            } elseif ($att && $att->status === 'on_leave') {
                // counted in leave section
            } else {
                $daysAbsent++;
            }
        }

        // --- Gather leave (approved, overlapping period) ---
        $paidLeaveDays = $unpaidLeaveDays = 0;
        $leaves = LeaveRequest::forUser($user->id)->where('status', 'approved')
            ->where('start_date', '<=', $end->toDateString())
            ->where('end_date', '>=', $start->toDateString())
            ->with('leaveType')->get();

        foreach ($leaves as $leave) {
            $days = $this->leaveDaysInPeriod($leave, $start, $end, $shift);
            if ($leave->leaveType->is_paid) {
                $paidLeaveDays += $days;
            } else {
                $unpaidLeaveDays += $days;
            }
        }

        // --- Compute ---
        $basic = round((float) $structure->basic_salary, 2);
        $overtimePay = round($overtimeMinutes * $minuteRate * (float) $structure->overtime_rate_multiplier, 2);

        $lateRate = (float) $structure->late_deduction_per_minute ?: $minuteRate;
        $lateDeduction = round($lateMinutes * $lateRate, 2);
        $absenceDeduction = round($daysAbsent * $dailyRate, 2);
        $unpaidLeaveDeduction = round($unpaidLeaveDays * $dailyRate, 2);

        $allowances = collect($structure->allowances ?? []);
        $allowancesTotal = round($allowances->sum(fn ($a) => (float) ($a['amount'] ?? 0)), 2);

        $perfect = ($daysAbsent === 0 && $lateMinutes === 0 && $unpaidLeaveDays === 0)
            ? round((float) $structure->perfect_attendance_bonus, 2) : 0.0;
        $bonuses = $perfect;

        $gross = round($basic + $overtimePay + $bonuses + $allowancesTotal, 2);
        $deductionsTotal = round($lateDeduction + $absenceDeduction + $unpaidLeaveDeduction, 2);
        $netPay = round(max(0, $gross - $deductionsTotal), 2);

        return DB::transaction(function () use (
            $user, $hr, $start, $end, $basic, $overtimePay, $bonuses, $allowancesTotal, $gross,
            $lateDeduction, $absenceDeduction, $unpaidLeaveDeduction, $deductionsTotal, $netPay,
            $daysPresent, $daysAbsent, $lateMinutes, $overtimeMinutes, $paidLeaveDays, $unpaidLeaveDays,
            $allowances, $perfect
        ) {
            $payroll = Payroll::create([
                'user_id' => $user->id,
                'period_start' => $start->toDateString(),
                'period_end' => $end->toDateString(),
                'label' => $start->format('M Y'),
                'basic' => $basic,
                'overtime_pay' => $overtimePay,
                'bonuses' => $bonuses,
                'allowances_total' => $allowancesTotal,
                'gross' => $gross,
                'late_deduction' => $lateDeduction,
                'absence_deduction' => $absenceDeduction,
                'unpaid_leave_deduction' => $unpaidLeaveDeduction,
                'other_deductions' => 0,
                'deductions_total' => $deductionsTotal,
                'net_pay' => $netPay,
                'status' => 'draft',
                'generated_by' => $hr->id,
                'generated_at' => now(),
                'meta' => [
                    'days_present' => $daysPresent,
                    'days_absent' => $daysAbsent,
                    'late_minutes' => $lateMinutes,
                    'overtime_minutes' => $overtimeMinutes,
                    'paid_leave_days' => $paidLeaveDays,
                    'unpaid_leave_days' => $unpaidLeaveDays,
                ],
            ]);

            // Line items
            $items = [
                ['type' => 'earning', 'code' => 'basic', 'label' => 'Basic Salary', 'amount' => $basic],
                ['type' => 'earning', 'code' => 'overtime', 'label' => "Overtime ({$overtimeMinutes} min)", 'amount' => $overtimePay],
            ];
            foreach ($allowances as $a) {
                $items[] = ['type' => 'earning', 'code' => 'allowance', 'label' => $a['name'] ?? 'Allowance', 'amount' => round((float) ($a['amount'] ?? 0), 2)];
            }
            if ($perfect > 0) {
                $items[] = ['type' => 'earning', 'code' => 'bonus_perfect', 'label' => 'Perfect Attendance Bonus', 'amount' => $perfect];
            }
            $items[] = ['type' => 'deduction', 'code' => 'late', 'label' => "Late ({$lateMinutes} min)", 'amount' => $lateDeduction];
            $items[] = ['type' => 'deduction', 'code' => 'absence', 'label' => "Absence ({$daysAbsent} day)", 'amount' => $absenceDeduction];
            $items[] = ['type' => 'deduction', 'code' => 'unpaid_leave', 'label' => "Unpaid Leave ({$unpaidLeaveDays} day)", 'amount' => $unpaidLeaveDeduction];

            foreach ($items as $item) {
                $payroll->items()->create($item);
            }

            $this->audit->log('payroll.generated', $payroll, [], [
                'net_pay' => $netPay, 'period' => $start->toDateString().' to '.$end->toDateString(),
            ], "Generated payroll for {$user->name}");

            return $payroll;
        });
    }

    /**
     * @return array{generated:int, skipped:int, failed:array<int,string>}
     */
    public function generateBatch(Carbon $start, Carbon $end, User $hr, ?int $departmentId = null): array
    {
        $query = User::active()->whereHas('salaryStructures', fn ($q) => $q->where('is_active', true));
        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }

        $generated = 0;
        $skipped = 0;
        $failed = [];

        foreach ($query->get() as $user) {
            try {
                $this->generate($user, $start, $end, $hr);
                $generated++;
            } catch (RuntimeException $e) {
                if (str_contains($e->getMessage(), 'already exists')) {
                    $skipped++;
                } else {
                    $failed[] = "{$user->name}: {$e->getMessage()}";
                }
            }
        }

        return ['generated' => $generated, 'skipped' => $skipped, 'failed' => $failed];
    }

    public function finalize(Payroll $payroll): Payroll
    {
        $payroll->update(['status' => 'finalized', 'finalized_at' => now()]);
        $this->audit->log('payroll.finalized', $payroll, ['status' => 'draft'], ['status' => 'finalized']);

        return $payroll;
    }

    public function markPaid(Payroll $payroll): Payroll
    {
        $payroll->update(['status' => 'paid', 'paid_at' => now()]);
        $this->audit->log('payroll.paid', $payroll, [], ['status' => 'paid']);

        return $payroll;
    }

    private function leaveDaysInPeriod(LeaveRequest $leave, Carbon $start, Carbon $end, $shift): int
    {
        $from = Carbon::parse($leave->start_date)->max($start);
        $to = Carbon::parse($leave->end_date)->min($end);
        $days = 0;
        for ($d = $from->copy(); $d->lessThanOrEqualTo($to); $d->addDay()) {
            if (! $shift || $shift->worksOn($d)) {
                $days++;
            }
        }

        return $days;
    }
}
