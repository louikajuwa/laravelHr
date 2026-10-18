<?php

namespace Database\Factories;

use App\Models\LeaveType;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<LeaveType> */
class LeaveTypeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'code' => strtoupper(fake()->unique()->lexify('???')),
            'default_days_per_year' => 10,
            'is_paid' => true,
            'requires_approval' => true,
            'carry_over' => false,
            'max_carry_over_days' => 0,
            'is_active' => true,
        ];
    }
}
