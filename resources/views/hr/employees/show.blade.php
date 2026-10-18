@extends('layouts.app')
@section('title', $employee->name)

@section('content')
    <x-page-header :title="$employee->name" :subtitle="$employee->employee_code">
        <x-slot:actions>
            <x-button variant="secondary" :href="route('hr.employees.edit', $employee)">Edit</x-button>
            <x-button :href="route('hr.salary-structures.create', ['user_id' => $employee->id])">Set Salary</x-button>
        </x-slot:actions>
    </x-page-header>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <x-card title="Profile" class="lg:col-span-1">
            <div class="flex items-center gap-3">
                <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-accent-100 text-lg font-bold text-accent-700">{{ $employee->initials() }}</span>
                <div>
                    <p class="font-semibold text-slate-800">{{ $employee->name }}</p>
                    <p class="text-sm text-slate-500">{{ $employee->email }}</p>
                    @if ($employee->is_active)
                        <x-badge color="bg-emerald-100 text-emerald-700">Active</x-badge>
                    @else
                        <x-badge color="bg-slate-200 text-slate-600">Inactive</x-badge>
                    @endif
                </div>
            </div>
            <dl class="mt-5 space-y-2.5 text-sm">
                @foreach ([
                    'Department' => $employee->department?->name,
                    'Position' => $employee->jobPosition?->title,
                    'Shift' => $employee->shift?->name ?? $employee->department?->shift?->name,
                    'Employment' => ucfirst(str_replace('_', ' ', $employee->employment_type)),
                    'Phone' => $employee->phone,
                    'Hire Date' => $employee->hire_date?->format('M d, Y'),
                ] as $label => $value)
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">{{ $label }}</dt>
                        <dd class="text-right font-medium text-slate-700">{{ $value ?: '—' }}</dd>
                    </div>
                @endforeach
            </dl>
        </x-card>

        <div class="space-y-6 lg:col-span-2">
            <x-card title="Salary Structure">
                @if ($employee->activeSalaryStructure)
                    @php $s = $employee->activeSalaryStructure; @endphp
                    <div class="grid grid-cols-2 gap-4 text-sm sm:grid-cols-3">
                        <div><p class="text-slate-500">Basic</p><p class="font-semibold text-slate-800">{{ number_format($s->basic_salary, 2) }}</p></div>
                        <div><p class="text-slate-500">OT Multiplier</p><p class="font-semibold text-slate-800">{{ $s->overtime_rate_multiplier }}×</p></div>
                        <div><p class="text-slate-500">Late / min</p><p class="font-semibold text-slate-800">{{ number_format($s->late_deduction_per_minute, 2) }}</p></div>
                        <div><p class="text-slate-500">Perfect Bonus</p><p class="font-semibold text-slate-800">{{ number_format($s->perfect_attendance_bonus, 2) }}</p></div>
                        <div><p class="text-slate-500">Work Days/mo</p><p class="font-semibold text-slate-800">{{ $s->working_days_per_month }}</p></div>
                        <div><p class="text-slate-500">Effective</p><p class="font-semibold text-slate-800">{{ $s->effective_from->format('M d, Y') }}</p></div>
                    </div>
                @else
                    <x-empty message="No salary structure set." icon="cash">
                        <x-slot:action>
                            <x-button :href="route('hr.salary-structures.create', ['user_id' => $employee->id])">Set Salary</x-button>
                        </x-slot:action>
                    </x-empty>
                @endif
            </x-card>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <x-button variant="secondary" :href="route('hr.attendance.index', ['q' => $employee->employee_code])">View Attendance</x-button>
                <x-button variant="secondary" :href="route('hr.leave.index', ['q' => $employee->employee_code])">View Leave</x-button>
                <x-button variant="secondary" :href="route('hr.payroll.index', ['q' => $employee->employee_code])">View Payroll</x-button>
            </div>
        </div>
    </div>
@endsection
