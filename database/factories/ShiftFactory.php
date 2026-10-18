<?php

namespace Database\Factories;

use App\Models\Shift;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Shift> */
class ShiftFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word().' shift',
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'grace_period_minutes' => 10,
            'break_minutes' => 60,
            'working_days' => ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'],
            'overtime_threshold_minutes' => 0,
            'is_active' => true,
        ];
    }

    public function overnight(): static
    {
        return $this->state(fn () => ['start_time' => '22:00:00', 'end_time' => '06:00:00']);
    }
}
