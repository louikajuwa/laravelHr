<?php

use Illuminate\Support\Facades\Schedule;

// Close each working day's attendance (mark absentees) shortly after midnight.
Schedule::command('attendance:close-day')->dailyAt('00:05');

// Allocate fresh leave balances at the start of each year.
Schedule::command('leave:allocate')->yearlyOn(1, 1, '00:10');
