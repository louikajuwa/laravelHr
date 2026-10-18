<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CloseAttendanceDay extends Command
{
    protected $signature = 'attendance:close-day {date? : Y-m-d, defaults to today}';

    protected $description = 'Mark absentees for the day and close dangling clock-ins.';

    public function handle(): int
    {
        $date = $this->argument('date') ? Carbon::parse($this->argument('date')) : now();
        $dateStr = $date->toDateString();
        $created = 0;

        foreach (User::active()->get() as $user) {
            $shift = $user->effectiveShift();
            if ($shift && ! $shift->worksOn($date)) {
                continue;
            }

            $att = Attendance::firstOrNew(['user_id' => $user->id, 'date' => $dateStr]);
            if (! $att->exists) {
                $att->status = 'absent';
                $att->shift_id = $shift?->id;
                $att->save();
                $created++;
            }
        }

        $this->info("Closed {$dateStr}: {$created} absentee record(s) created.");

        return self::SUCCESS;
    }
}
