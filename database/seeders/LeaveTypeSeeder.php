<?php

namespace Database\Seeders;

use App\Models\LeaveType;
use Illuminate\Database\Seeder;

class LeaveTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'Sick Leave', 'code' => 'SL', 'default_days_per_year' => 10, 'is_paid' => true, 'carry_over' => false],
            ['name' => 'Vacation Leave', 'code' => 'VL', 'default_days_per_year' => 15, 'is_paid' => true, 'carry_over' => true, 'max_carry_over_days' => 5],
            ['name' => 'Emergency Leave', 'code' => 'EL', 'default_days_per_year' => 5, 'is_paid' => true, 'carry_over' => false],
            ['name' => 'Unpaid Leave', 'code' => 'UL', 'default_days_per_year' => 0, 'is_paid' => false, 'carry_over' => false],
        ];

        foreach ($types as $type) {
            LeaveType::create(array_merge([
                'requires_approval' => true, 'is_active' => true, 'max_carry_over_days' => 0,
            ], $type));
        }
    }
}
