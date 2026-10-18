<?php

namespace Database\Factories;

use App\Models\SalaryStructure;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SalaryStructure>
 */
class SalaryStructureFactory extends Factory
{
    protected $model = SalaryStructure::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'basic_salary' => 22000,
            'pay_frequency' => 'monthly',
            'working_days_per_month' => 22,
            'overtime_rate_multiplier' => 1.25,
            'late_deduction_per_minute' => 0,
            'absence_deduction_mode' => 'daily_rate',
            'perfect_attendance_bonus' => 500,
            'allowances' => null,
            'effective_from' => '2025-01-01',
            'effective_to' => null,
            'is_active' => true,
        ];
    }
}
