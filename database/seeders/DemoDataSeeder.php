<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Department;
use App\Models\JobPosition;
use App\Models\LeaveType;
use App\Models\SalaryStructure;
use App\Models\Shift;
use App\Models\User;
use App\Services\EmployeeService;
use App\Services\LeaveService;
use App\Services\PayrollService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use RuntimeException;

class DemoDataSeeder extends Seeder
{
    public function __construct(
        private EmployeeService $employees,
        private LeaveService $leaves,
        private PayrollService $payroll,
    ) {}

    public function run(): void
    {
        $hrDept = Department::where('code', 'HR')->first();
        $departments = Department::with('shift')->get();

        // --- Fixed demo accounts ---
        $admin = $this->makeEmployee([
            'name' => 'Alex HR Admin', 'email' => 'admin@hr.test', 'role' => 'admin',
            'department_id' => $hrDept->id, 'shift_id' => $hrDept->shift_id,
            'job_position_id' => JobPosition::where('title', 'HR Manager')->value('id'),
        ], 60000);

        $employee = $this->makeEmployee([
            'name' => 'Jamie Employee', 'email' => 'employee@hr.test', 'role' => 'employee',
            'department_id' => Department::where('code', 'IT')->value('id'),
            'shift_id' => Department::where('code', 'IT')->value('shift_id'),
            'job_position_id' => JobPosition::where('title', 'Software Engineer')->value('id'),
        ], 42000);

        // --- 15 random employees ---
        $staff = collect([$employee]);
        for ($i = 0; $i < 15; $i++) {
            $dept = $departments->random();
            $position = JobPosition::where('department_id', $dept->id)->inRandomOrder()->first();
            $staff->push($this->makeEmployee([
                'name' => fake()->name(),
                'email' => fake()->unique()->safeEmail(),
                'role' => 'employee',
                'department_id' => $dept->id,
                'shift_id' => $dept->shift_id,
                'job_position_id' => $position?->id,
            ], fake()->numberBetween(25, 55) * 1000));
        }

        // --- Attendance for the trailing 28 days (up to yesterday) ---
        foreach ($staff->push($admin) as $user) {
            $this->seedAttendance($user);
        }

        // --- Leave requests (mixed statuses) ---
        $this->seedLeave($staff);

        // --- Payroll for last full month, finalized ---
        $start = now()->subMonthNoOverflow()->startOfMonth();
        $end = $start->copy()->endOfMonth();
        $result = $this->payroll->generateBatch($start, $end, $admin);
        \App\Models\Payroll::query()->update(['status' => 'finalized', 'finalized_at' => now()]);

        $this->command?->info("Demo data: {$staff->count()} employees, payroll {$result['generated']} generated.");
    }

    /**
     * @param  array<string,mixed>  $data
     */
    private function makeEmployee(array $data, int $basic): User
    {
        $user = $this->employees->create(array_merge([
            'password' => 'password',
            'hire_date' => fake()->dateTimeBetween('-3 years', '-2 months')->format('Y-m-d'),
            'phone' => fake()->numerify('+1-###-###-####'),
            'employment_type' => 'full_time',
        ], $data));

        SalaryStructure::create([
            'user_id' => $user->id,
            'basic_salary' => $basic,
            'pay_frequency' => 'monthly',
            'working_days_per_month' => 22,
            'overtime_rate_multiplier' => 1.25,
            'late_deduction_per_minute' => 5,
            'absence_deduction_mode' => 'daily_rate',
            'perfect_attendance_bonus' => 1500,
            'allowances' => [['name' => 'Transport', 'amount' => 1000], ['name' => 'Meal', 'amount' => 800]],
            'effective_from' => now()->subMonths(6)->toDateString(),
            'is_active' => true,
        ]);

        return $user;
    }

    private function seedAttendance(User $user): void
    {
        $shift = $user->effectiveShift();
        if (! $shift) {
            return;
        }

        for ($offset = 28; $offset >= 1; $offset--) {
            $date = now()->subDays($offset)->startOfDay();
            if (! $shift->worksOn($date)) {
                continue;
            }

            $roll = fake()->numberBetween(1, 100);
            if ($roll <= 5) {
                Attendance::create([
                    'user_id' => $user->id, 'date' => $date->toDateString(),
                    'shift_id' => $shift->id, 'status' => 'absent', 'source' => 'import',
                ]);

                continue;
            }

            $expectedStart = $shift->expectedStart($date);
            $expectedEnd = $shift->expectedEnd($date);
            $grace = $expectedStart->copy()->addMinutes((int) $shift->grace_period_minutes);

            // Time in: 75% on-time/early, 25% late.
            if (fake()->numberBetween(1, 100) <= 25) {
                $timeIn = $expectedStart->copy()->addMinutes(fake()->numberBetween(5, 55));
            } else {
                $timeIn = $expectedStart->copy()->subMinutes(fake()->numberBetween(0, 12));
            }

            $isLate = $timeIn->greaterThan($grace);
            $lateMinutes = $isLate ? (int) abs($expectedStart->diffInMinutes($timeIn)) : 0;

            // Time out: 25% overtime.
            $timeOut = fake()->numberBetween(1, 100) <= 25
                ? $expectedEnd->copy()->addMinutes(fake()->numberBetween(20, 120))
                : $expectedEnd->copy()->addMinutes(fake()->numberBetween(-8, 8));

            $worked = max(0, (int) abs($timeIn->diffInMinutes($timeOut)) - (int) $shift->break_minutes);
            $overtime = $timeOut->greaterThan($expectedEnd)
                ? max(0, (int) abs($expectedEnd->diffInMinutes($timeOut)) - (int) $shift->overtime_threshold_minutes)
                : 0;

            Attendance::create([
                'user_id' => $user->id,
                'date' => $date->toDateString(),
                'shift_id' => $shift->id,
                'time_in' => $timeIn,
                'time_out' => $timeOut,
                'status' => $isLate ? 'late' : 'present',
                'is_late' => $isLate,
                'late_minutes' => $lateMinutes,
                'worked_minutes' => $worked,
                'overtime_minutes' => $overtime,
                'source' => 'import',
            ]);
        }
    }

    private function seedLeave($staff): void
    {
        $types = LeaveType::where('is_active', true)->get();

        foreach ($staff->take(8) as $i => $user) {
            $type = $types->random();
            $startOffset = fake()->numberBetween(3, 20);
            $start = now()->subDays($startOffset);
            $end = $start->copy()->addDays(fake()->numberBetween(0, 3));

            try {
                $leave = $this->leaves->request($user, [
                    'leave_type_id' => $type->id,
                    'start_date' => $start->toDateString(),
                    'end_date' => $end->toDateString(),
                    'reason' => fake()->sentence(),
                ]);

                // ~half approved, some rejected, rest left pending.
                $admin = User::role('admin')->first();
                $decision = fake()->numberBetween(1, 100);
                if ($decision <= 55) {
                    $this->leaves->approve($leave, $admin, 'Approved.');
                } elseif ($decision <= 75) {
                    $this->leaves->reject($leave, $admin, 'Insufficient coverage.');
                }
            } catch (RuntimeException $e) {
                // Skip overlaps / insufficient balance.
            }
        }
    }
}
