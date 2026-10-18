<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\JobPosition;
use App\Models\Shift;
use Illuminate\Database\Seeder;

class DepartmentShiftSeeder extends Seeder
{
    public function run(): void
    {
        $weekdays = ['mon', 'tue', 'wed', 'thu', 'fri'];

        $day = Shift::create([
            'name' => 'Day Shift', 'start_time' => '09:00', 'end_time' => '18:00',
            'grace_period_minutes' => 15, 'break_minutes' => 60, 'working_days' => $weekdays,
            'overtime_threshold_minutes' => 0, 'is_active' => true,
        ]);
        $morning = Shift::create([
            'name' => 'Morning Shift', 'start_time' => '06:00', 'end_time' => '15:00',
            'grace_period_minutes' => 10, 'break_minutes' => 60, 'working_days' => $weekdays,
            'overtime_threshold_minutes' => 0, 'is_active' => true,
        ]);
        Shift::create([
            'name' => 'Night Shift', 'start_time' => '22:00', 'end_time' => '07:00',
            'grace_period_minutes' => 15, 'break_minutes' => 60, 'working_days' => $weekdays,
            'overtime_threshold_minutes' => 30, 'is_active' => true,
        ]);

        $departments = [
            'IT' => ['code' => 'IT', 'shift' => $day, 'positions' => ['Software Engineer', 'QA Analyst', 'DevOps Engineer', 'IT Support']],
            'Human Resources' => ['code' => 'HR', 'shift' => $day, 'positions' => ['HR Manager', 'HR Officer', 'Recruiter']],
            'Sales' => ['code' => 'SAL', 'shift' => $morning, 'positions' => ['Sales Executive', 'Account Manager', 'Sales Lead']],
            'Finance' => ['code' => 'FIN', 'shift' => $day, 'positions' => ['Accountant', 'Payroll Officer', 'Financial Analyst']],
            'Operations' => ['code' => 'OPS', 'shift' => $morning, 'positions' => ['Operations Officer', 'Logistics Coordinator']],
        ];

        foreach ($departments as $name => $meta) {
            $dept = Department::create([
                'name' => $name, 'code' => $meta['code'], 'shift_id' => $meta['shift']->id, 'is_active' => true,
            ]);
            foreach ($meta['positions'] as $title) {
                JobPosition::create(['title' => $title, 'department_id' => $dept->id]);
            }
        }
    }
}
