<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CloseAttendanceDay extends Command
{
    protected $signature = 'attendance:close-day {date? : Y-m-d, defaults to yesterday} {--date= : Y-m-d (same as the argument)}';

    protected $description = 'Mark absentees for the day and close dangling clock-ins.';

    public function handle(): int
    {
        $input = $this->argument('date') ?: $this->option('date');
        $date = $input ? Carbon::parse($input) : now()->subDay();
        $dateStr = $date->toDateString();
        $created = 0;

        foreach (User::active()->get() as $user) {
            $shift = $user->effectiveShift();
            if ($shift && ! $shift->worksOn($date)) {
                continue;
            }

            $att = Attendance::firstOrNew(['user_id' => $user->id, 'date' => $dateStr]);
            if (! $att->exists) {
                $onLeave = LeaveRequest::forUser($user->id)->where('status', 'approved')
                    ->where('start_date', '<=', $dateStr)->where('end_date', '>=', $dateStr)->exists();
                $att->status = $onLeave ? 'on_leave' : 'absent';
                $att->shift_id = $shift?->id;
                $att->save();
                $created++;
            }
        }

        $this->info("Closed {$dateStr}: {$created} absentee record(s) created.");

        return self::SUCCESS;
    }
}
