<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Shift;
use App\Models\User;
use App\Support\BusinessTime;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AttendanceService
{
    public function __construct(private AuditLogger $audit) {}

    public function clockIn(User $user, ?Carbon $at = null): Attendance
    {
        $at = $at ? BusinessTime::local($at) : BusinessTime::now();
        $date = $at->toDateString();

        $attendance = Attendance::firstOrNew(['user_id' => $user->id, 'date' => $date]);

        if ($attendance->time_in) {
            throw new RuntimeException('You have already clocked in today.');
        }

        $shift = $user->effectiveShift();
        $wasOnLeave = $attendance->exists && $attendance->status === 'on_leave';
        $attendance->shift_id = $shift?->id;
        $attendance->time_in = BusinessTime::toStorage($at);
        $attendance->source = 'web_clock';
        if ($wasOnLeave) {
            // Employee showed up on an approved-leave day: allow it, but keep a trace in remarks.
            $attendance->remarks = trim(($attendance->remarks ? $attendance->remarks.' | ' : '').'Clocked in during approved leave');
        }

        $this->applyLate($attendance, $shift, $at);
        $attendance->save();

        $this->audit->log('attendance.clock_in', $attendance, [], [
            'time_in_epoch' => $at->getTimestamp(),
            'is_late' => $attendance->is_late,
            'late_minutes' => $attendance->late_minutes,
        ], "Clocked in {$user->name}", $user->id);

        return $attendance;
    }

    public function clockOut(User $user, ?Carbon $at = null): Attendance
    {
        $at = $at ? BusinessTime::local($at) : BusinessTime::now();
        $date = $at->toDateString();

        $attendance = Attendance::where('user_id', $user->id)->where('date', $date)->first();

        if (! $attendance || ! $attendance->time_in) {
            // Overnight shift: the open row belongs to yesterday.
            $prev = Attendance::where('user_id', $user->id)
                ->where('date', $at->copy()->subDay()->toDateString())
                ->whereNotNull('time_in')->whereNull('time_out')->first();
            $prevShift = $prev ? ($prev->shift ?? $user->effectiveShift()) : null;
            if ($prev && $prevShift?->crossesMidnight()) {
                $attendance = $prev;
            }
        }

        if (! $attendance || ! $attendance->time_in) {
            throw new RuntimeException('You must clock in before clocking out.');
        }
        if ($attendance->time_out) {
            throw new RuntimeException('You have already clocked out today.');
        }

        $attendance->time_out = BusinessTime::toStorage($at);
        $this->applyDurations($attendance, $attendance->shift);
        $attendance->save();

        $this->audit->log('attendance.clock_out', $attendance, [], [
            'time_out_epoch' => $at->getTimestamp(),
            'worked_minutes' => $attendance->worked_minutes,
            'overtime_minutes' => $attendance->overtime_minutes,
        ], "Clocked out {$user->name}", $user->id);

        return $attendance;
    }

    /**
     * HR manual correction. Records a per-field correction row + audit, then recomputes.
     *
     * @param  array<string,mixed>  $changes  keys: time_in, time_out, status, remarks
     */
    public function correct(Attendance $attendance, array $changes, User $hr, string $reason): Attendance
    {
        return DB::transaction(function () use ($attendance, $changes, $hr, $reason) {
            $before = $attendance->only(['time_in', 'time_out', 'status', 'remarks']);

            foreach (['time_in', 'time_out', 'status', 'remarks'] as $field) {
                if (! array_key_exists($field, $changes)) {
                    continue;
                }
                $new = $changes[$field] ?: null;
                if ($new !== null && in_array($field, ['time_in', 'time_out'], true)) {
                    $new = BusinessTime::toStorage(BusinessTime::parse((string) $new));
                }
                $old = $attendance->$field;
                $oldStr = $old instanceof Carbon ? $old->toDateTimeString() : (string) $old;
                $newStr = $new instanceof Carbon ? $new->toDateTimeString() : (string) $new;
                if ($oldStr === $newStr) {
                    continue;
                }

                $attendance->corrections()->create([
                    'corrected_by' => $hr->id,
                    'field' => $field,
                    'old_value' => $oldStr,
                    'new_value' => $newStr,
                    'reason' => $reason,
                ]);

                $attendance->$field = $new;
            }

            // HR-chosen absent/half_day/holiday/on_leave is authoritative; only present/late (or empty) is recomputed.
            $recomputeStatus = in_array($attendance->status, ['present', 'late', '', null], true);
            if ($attendance->time_in && $attendance->shift && $recomputeStatus) {
                $this->applyLate($attendance, $attendance->shift, Carbon::parse($attendance->time_in));
            }
            if ($attendance->time_in && $attendance->time_out) {
                $this->applyDurations($attendance, $attendance->shift);
            }

            $attendance->is_manual = true;
            $attendance->source = 'manual';
            $attendance->save();

            $this->audit->log('attendance.corrected', $attendance,
                $before, $attendance->only(['time_in', 'time_out', 'status', 'remarks']),
                "Corrected attendance #{$attendance->id}: {$reason}");

            return $attendance;
        });
    }

    private function applyLate(Attendance $attendance, ?Shift $shift, Carbon $at): void
    {
        if (! $shift) {
            $attendance->is_late = false;
            $attendance->late_minutes = 0;
            $attendance->status = 'present';

            return;
        }

        $expectedStart = $shift->expectedStart(Carbon::parse($attendance->date));
        $graceLimit = $expectedStart->copy()->addMinutes((int) $shift->grace_period_minutes);

        if ($at->greaterThan($graceLimit)) {
            $attendance->is_late = true;
            $attendance->late_minutes = (int) abs($expectedStart->diffInMinutes($at));
            $attendance->status = 'late';
        } else {
            $attendance->is_late = false;
            $attendance->late_minutes = 0;
            $attendance->status = 'present';
        }
    }

    private function applyDurations(Attendance $attendance, ?Shift $shift): void
    {
        $in = Carbon::parse($attendance->time_in);
        $out = Carbon::parse($attendance->time_out);
        if ($out->lessThanOrEqualTo($in)) {
            $out->addDay();
        }

        $break = $shift ? (int) $shift->break_minutes : 0;
        $attendance->worked_minutes = max(0, (int) abs($in->diffInMinutes($out)) - $break);

        $attendance->early_leave_minutes = 0;
        $attendance->overtime_minutes = 0;

        if ($shift) {
            $expectedEnd = $shift->expectedEnd(Carbon::parse($attendance->date));
            $delta = (int) abs($out->diffInMinutes($expectedEnd));
            if ($out->lessThan($expectedEnd)) {
                $attendance->early_leave_minutes = $delta;
            } else {
                $attendance->overtime_minutes = max(0, $delta - (int) $shift->overtime_threshold_minutes);
            }
        }
    }
}
