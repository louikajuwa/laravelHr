<?php

namespace Database\Factories;

use App\Models\Payroll;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payroll>
 */
class PayrollFactory extends Factory
{
    protected $model = Payroll::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'period_start' => '2026-01-01',
            'period_end' => '2026-01-31',
            'label' => 'Jan 2026',
            'basic' => 22000,
            'gross' => 22000,
            'deductions_total' => 0,
            'net_pay' => 22000,
            'status' => 'draft',
            'generated_at' => now(),
        ];
    }

    public function status(string $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }
}
