<?php

namespace App\Console\Commands;

use App\Services\LeaveService;
use Illuminate\Console\Command;

class AllocateLeaveBalances extends Command
{
    protected $signature = 'leave:allocate {year? : defaults to current year}';

    protected $description = 'Allocate/refresh yearly leave balances for all active employees.';

    public function handle(LeaveService $leaves): int
    {
        $year = (int) ($this->argument('year') ?: now()->year);
        $count = $leaves->allocateYearly($year);

        $this->info("Allocated leave balances for {$year}: {$count} record(s).");

        return self::SUCCESS;
    }
}
