<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class LeaveService
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * @param  array<string,mixed>  $data  keys: leave_type_id, start_date, end_date, reason
     */
    public function request(User $user, array $data): LeaveRequest
    {
        return DB::transaction(function () use ($user, $data) {
            $type = LeaveType::findOrFail($data['leave_type_id']);
            $start = Carbon::parse($data['start_date']);
            $end = Carbon::parse($data['end_date']);

            if ($end->lessThan($start)) {
                throw new RuntimeException('End date must be on or after the start date.');
            }

            // Serialize concurrent requests for the same balance before checking overlap/availability.
            $balance = $this->lockedBalance($user, $type, (int) $start->year);

            if ($this->hasOverlap($user, $start, $end)) {
                throw new RuntimeException('You already have a leave request overlapping these dates.');
            }

            $days = $this->workingDaysBetween($user, $start, $end);
            if ($days <= 0) {
                throw new RuntimeException('The selected range contains no working days.');
            }

            if ($type->is_paid && $balance->available < $days) {
                throw new RuntimeException("Insufficient {$type->name} balance ({$balance->available} day(s) left).");
            }

            $leave = LeaveRequest::create([
                'user_id' => $user->id,
                'leave_type_id' => $type->id,
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'days' => $days,
                'reason' => $data['reason'] ?? null,
                'status' => 'pending',
            ]);

            $balance->increment('pending', $days);

            $this->audit->log('leave.requested', $leave, [], [
                'type' => $type->code, 'days' => $days,
                'range' => $start->toDateString().' to '.$end->toDateString(),
            ], "Leave requested by {$user->name}", $user->id);

            return $leave;
        });
    }

    public function approve(LeaveRequest $leave, User $approver, ?string $note = null): LeaveRequest
    {
        return DB::transaction(function () use ($leave, $approver, $note) {
            $leave = $this->lockPending($leave);

            $leave->update([
                'status' => 'approved',
                'approver_id' => $approver->id,
                'decided_at' => now(),
                'decision_note' => $note,
            ]);

            $balance = $this->lockedBalance($leave->user, $leave->leaveType, (int) Carbon::parse($leave->start_date)->year);
            $balance->decrement('pending', (float) $leave->days);
            $balance->increment('used', (float) $leave->days);

            $this->writeLeaveAttendance($leave);

            $this->audit->log('leave.approved', $leave, ['status' => 'pending'],
                ['status' => 'approved', 'note' => $note], "Approved leave #{$leave->id}");

            return $leave;
        });
    }

    public function reject(LeaveRequest $leave, User $approver, ?string $note = null): LeaveRequest
    {
        return DB::transaction(function () use ($leave, $approver, $note) {
            $leave = $this->lockPending($leave);

            $leave->update([
                'status' => 'rejected',
                'approver_id' => $approver->id,
                'decided_at' => now(),
                'decision_note' => $note,
            ]);

            $this->lockedBalance($leave->user, $leave->leaveType, (int) Carbon::parse($leave->start_date)->year)
                ->decrement('pending', (float) $leave->days);

            $this->audit->log('leave.rejected', $leave, ['status' => 'pending'],
                ['status' => 'rejected', 'note' => $note], "Rejected leave #{$leave->id}");

            return $leave;
        });
    }

    public function cancel(LeaveRequest $leave): LeaveRequest
    {
        return DB::transaction(function () use ($leave) {
            $leave = $this->lockPending($leave);

            $leave->update(['status' => 'cancelled']);
            $this->lockedBalance($leave->user, $leave->leaveType, (int) Carbon::parse($leave->start_date)->year)
                ->decrement('pending', (float) $leave->days);

            $this->audit->log('leave.cancelled', $leave, ['status' => 'pending'],
                ['status' => 'cancelled'], "Cancelled leave #{$leave->id}", $leave->user_id);

            return $leave;
        });
    }

    public function allocateYearly(int $year): int
    {
        $count = 0;
        $types = LeaveType::where('is_active', true)->get();

        foreach (User::active()->get() as $user) {
            foreach ($types as $type) {
                $user->leaveBalances()->updateOrCreate(
                    ['leave_type_id' => $type->id, 'year' => $year],
                    ['allocated' => $type->default_days_per_year],
                );
                $count++;
            }
        }

        return $count;
    }

    public function balanceFor(User $user, LeaveType $type, int $year): LeaveBalance
    {
        return LeaveBalance::firstOrCreate(
            ['user_id' => $user->id, 'leave_type_id' => $type->id, 'year' => $year],
            ['allocated' => $type->default_days_per_year],
        );
    }

    public function workingDaysBetween(User $user, Carbon $start, Carbon $end): int
    {
        $shift = $user->effectiveShift();
        $days = 0;
        for ($d = $start->copy(); $d->lessThanOrEqualTo($end); $d->addDay()) {
            if (! $shift || $shift->worksOn($d)) {
                $days++;
            }
        }

        return $days;
    }

    private function writeLeaveAttendance(LeaveRequest $leave): void
    {
        $shift = $leave->user->effectiveShift();
        $start = Carbon::parse($leave->start_date);
        $end = Carbon::parse($leave->end_date);

        for ($d = $start->copy(); $d->lessThanOrEqualTo($end); $d->addDay()) {
            if ($shift && ! $shift->worksOn($d)) {
                continue;
            }
            $att = Attendance::firstOrNew(['user_id' => $leave->user_id, 'date' => $d->toDateString()]);
            if ($att->exists && $att->time_in) {
                continue; // never overwrite a day the employee actually worked
            }
            $att->status = 'on_leave';
            $att->shift_id = $shift?->id;
            $att->remarks = $leave->leaveType->code.' leave';
            $att->save();
        }
    }

    private function hasOverlap(User $user, Carbon $start, Carbon $end): bool
    {
        return LeaveRequest::forUser($user->id)
            ->whereIn('status', ['pending', 'approved'])
            ->where('start_date', '<=', $end->toDateString())
            ->where('end_date', '>=', $start->toDateString())
            ->exists();
    }

    private function lockedBalance(User $user, LeaveType $type, int $year): LeaveBalance
    {
        $this->balanceFor($user, $type, $year);

        return LeaveBalance::where(['user_id' => $user->id, 'leave_type_id' => $type->id, 'year' => $year])
            ->lockForUpdate()->firstOrFail();
    }

    /** Re-fetch the request under a row lock (must run inside a transaction) and ensure it is still pending. */
    private function lockPending(LeaveRequest $leave): LeaveRequest
    {
        $fresh = LeaveRequest::whereKey($leave->getKey())->lockForUpdate()->firstOrFail();
        $this->guardPending($fresh);

        return $fresh;
    }

    private function guardPending(LeaveRequest $leave): void
    {
        if ($leave->status !== 'pending') {
            throw new RuntimeException('Only pending leave requests can be updated.');
        }
    }
}
