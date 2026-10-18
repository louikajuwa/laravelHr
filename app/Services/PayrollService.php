<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\LateArrivalReport;
use App\Models\LeaveRequest;
use App\Models\Payroll;
use App\Models\User;
use App\Support\BusinessTime;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PayrollService
{
    public const CODE_FREQUENCY_MISMATCH = 422;

    public function __construct(private AuditLogger $audit) {}

    public function generate(User $user, Carbon $start, Carbon $end, User $hr): Payroll
    {
        $this->assertNoOverlap($user, $start, $end);

        $structure = $user->salaryStructures()->activeOn($start->toDateString())->first();
        if (! $structure) {
            throw new RuntimeException("No active salary structure for {$user->name}.");
        }

        $isFullMonth = $start->day === 1 && $end->isSameDay($start->copy()->endOfMonth());
        if ($structure->pay_frequency === 'biweekly' && $isFullMonth) {
            throw new RuntimeException("{$user->name} is paid semi-monthly; generate 1–15 or 16–end.", self::CODE_FREQUENCY_MISMATCH);
        }
        if ($structure->pay_frequency !== 'biweekly' && ! $isFullMonth) {
            throw new RuntimeException("{$user->name} is paid monthly; generate a full-month period.", self::CODE_FREQUENCY_MISMATCH);
        }
        // Monthly-denominated amounts (basic, allowances, perfect bonus) are halved for semi-monthly periods.
        $factor = $isFullMonth ? 1.0 : 0.5;
        $label = $isFullMonth
            ? $start->format('M Y')
            : $start->format('M Y').' ('.($start->day === 1 ? '1–15' : '16–'.$end->day).')';

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

        $today = BusinessTime::today();
        $hireDate = $user->hire_date?->copy()->startOfDay();

        for ($d = $start->copy(); $d->lessThanOrEqualTo($end); $d->addDay()) {
            if ($shift && ! $shift->worksOn($d)) {
                continue;
            }
            // Not employed yet, or the day has not happened yet: never absent.
            if (($hireDate && $d->lt($hireDate)) || $d->gt($today)) {
                continue;
            }
            $att = $attendances->get($d->toDateString());

            if ($att && in_array($att->status, ['present', 'late'], true)) {
                $daysPresent++;
                if (! in_array($d->toDateString(), $excusedDates, true)) {
                    $lateMinutes += (int) $att->late_minutes;
                }
                $overtimeMinutes += (int) $att->overtime_minutes;
            } elseif ($att && $att->status === 'half_day') {
                $daysPresent += 0.5;
                $daysAbsent += 0.5;
            } elseif ($att && in_array($att->status, ['on_leave', 'holiday'], true)) {
                // on_leave is counted in the leave section; holiday is simply not absent
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
        $basic = round((float) $structure->basic_salary * $factor, 2);
        $overtimePay = round($overtimeMinutes * $minuteRate * (float) $structure->overtime_rate_multiplier, 2);

        $lateRate = (float) $structure->late_deduction_per_minute ?: $minuteRate;
        $lateDeduction = round($lateMinutes * $lateRate, 2);
        $absenceDeduction = round($daysAbsent * $dailyRate, 2);
        $unpaidLeaveDeduction = round($unpaidLeaveDays * $dailyRate, 2);

        $allowances = collect($structure->allowances ?? []);
        $allowancesTotal = round($allowances->sum(fn ($a) => (float) ($a['amount'] ?? 0)) * $factor, 2);

        $perfect = ($daysAbsent == 0 && $lateMinutes === 0 && $unpaidLeaveDays === 0)
            ? round((float) $structure->perfect_attendance_bonus * $factor, 2) : 0.0;
        $bonuses = $perfect;

        $gross = round($basic + $overtimePay + $bonuses + $allowancesTotal, 2);
        $deductionsTotal = round($lateDeduction + $absenceDeduction + $unpaidLeaveDeduction, 2);
        // NOTE: net pay is floored at 0 (deductions can never make it negative); the excess is not carried over.
        $netPay = round(max(0, $gross - $deductionsTotal), 2);

        return DB::transaction(function () use (
            $user, $hr, $start, $end, $basic, $overtimePay, $bonuses, $allowancesTotal, $gross,
            $lateDeduction, $absenceDeduction, $unpaidLeaveDeduction, $deductionsTotal, $netPay,
            $daysPresent, $daysAbsent, $lateMinutes, $overtimeMinutes, $paidLeaveDays, $unpaidLeaveDays,
            $allowances, $perfect, $label, $factor
        ) {
            // Serialize per employee and re-check overlap inside the transaction.
            User::withTrashed()->whereKey($user->id)->lockForUpdate()->first();
            $this->assertNoOverlap($user, $start, $end);

            $payroll = Payroll::create([
                'user_id' => $user->id,
                'period_start' => $start->toDateString(),
                'period_end' => $end->toDateString(),
                'label' => $label,
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
                $items[] = ['type' => 'earning', 'code' => 'allowance', 'label' => $a['name'] ?? 'Allowance', 'amount' => round((float) ($a['amount'] ?? 0) * $factor, 2)];
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
     * @return array{generated:int, skipped:int, mismatched:int, skipped_reasons:array<int,string>, failed:array<int,string>}
     */
    public function generateBatch(Carbon $start, Carbon $end, User $hr, ?int $departmentId = null): array
    {
        $query = User::active()->whereHas('salaryStructures', fn ($q) => $q->activeOn($start->toDateString()));
        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }

        $generated = 0;
        $skipped = 0;
        $mismatched = 0;
        $skippedReasons = [];
        $failed = [];

        foreach ($query->get() as $user) {
            try {
                $this->generate($user, $start, $end, $hr);
                $generated++;
            } catch (QueryException $e) {
                // Unique-constraint race: another request created this payroll first.
                $skipped++;
            } catch (RuntimeException $e) {
                if ($e->getCode() === self::CODE_FREQUENCY_MISMATCH) {
                    $skipped++;
                    $mismatched++;
                    $skippedReasons[] = $e->getMessage();
                } elseif (str_contains($e->getMessage(), 'already exists')) {
                    $skipped++;
                } else {
                    $failed[] = "{$user->name}: {$e->getMessage()}";
                }
            }
        }

        return ['generated' => $generated, 'skipped' => $skipped, 'mismatched' => $mismatched, 'skipped_reasons' => $skippedReasons, 'failed' => $failed];
    }

    public function finalize(Payroll $payroll): Payroll
    {
        return $this->transition($payroll, 'draft', 'finalized', 'finalized_at', 'payroll.finalized');
    }

    public function markPaid(Payroll $payroll): Payroll
    {
        return $this->transition($payroll, 'finalized', 'paid', 'paid_at', 'payroll.paid');
    }

    private function transition(Payroll $payroll, string $from, string $to, string $stampColumn, string $action): Payroll
    {
        return DB::transaction(function () use ($payroll, $from, $to, $stampColumn, $action) {
            $fresh = Payroll::whereKey($payroll->getKey())->lockForUpdate()->firstOrFail();

            if ($fresh->status !== $from) {
                throw new RuntimeException("Payroll is {$fresh->status}; only a {$from} payroll can become {$to}.");
            }

            $fresh->update(['status' => $to, $stampColumn => now()]);
            $this->audit->log($action, $fresh, ['status' => $from], ['status' => $to]);
            $payroll->setRawAttributes($fresh->getAttributes(), true);

            return $payroll;
        });
    }

    private function assertNoOverlap(User $user, Carbon $start, Carbon $end): void
    {
        $exists = Payroll::where('user_id', $user->id)
            ->where('period_start', '<=', $end->toDateString())
            ->where('period_end', '>=', $start->toDateString())
            ->exists();

        if ($exists) {
            throw new RuntimeException("Payroll for {$user->name} in this period already exists (overlapping period).");
        }
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
