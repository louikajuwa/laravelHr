@extends('layouts.app')
@section('title', 'HR Dashboard')

@section('content')
    <x-page-header title="HR Dashboard" subtitle="Attendance, leave and payroll at a glance." />

    <div id="hr-dashboard" data-metrics-url="{{ route('hr.dashboard.metrics') }}">
        {{-- KPI cards --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-stat label="Active Employees" :value="$headcount['active']" :sub="$headcount['new_this_month'].' new this month'" icon="users" tone="accent" />
            <x-stat label="Present Today" :value="$today['present'] + $today['late']" :sub="$today['late'].' late · '.$today['absent'].' absent'" icon="clock" tone="emerald" />
            <x-stat label="Pending Leave" :value="$leave['pending']" sub="Awaiting your approval" icon="calendar" tone="amber" />
            <x-stat label="Net Payroll" :value="number_format($payroll['total_net'], 2)" :sub="$payroll['period'] ? $payroll['period'].' · '.$payroll['headcount'].' staff' : 'No payroll yet'" icon="cash" tone="violet" />
        </div>

        <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
            <x-card title="Attendance Trend" subtitle="Last 30 days" class="lg:col-span-2">
                <canvas id="chart-trend" height="120"></canvas>
            </x-card>
            <x-card title="Today" subtitle="Live attendance split">
                <canvas id="chart-today" height="200"></canvas>
            </x-card>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
            <x-card title="Approved Leave by Type" class="lg:col-span-2">
                <canvas id="chart-leave" height="110"></canvas>
            </x-card>

            <x-card title="Pending Actions">
                <ul class="divide-y divide-slate-100">
                    <li class="flex items-center justify-between py-3">
                        <span class="flex items-center gap-2 text-sm text-slate-600"><x-icon name="calendar" class="h-4 w-4 text-amber-500" /> Leave approvals</span>
                        <a href="{{ route('hr.leave.index', ['status' => 'pending']) }}" class="flex items-center gap-2">
                            <x-badge color="bg-amber-100 text-amber-700">{{ $queue['leave'] }}</x-badge>
                        </a>
                    </li>
                    <li class="flex items-center justify-between py-3">
                        <span class="flex items-center gap-2 text-sm text-slate-600"><x-icon name="alert" class="h-4 w-4 text-rose-500" /> Late arrival reviews</span>
                        <a href="{{ route('hr.late-arrivals.index', ['status' => 'pending']) }}">
                            <x-badge color="bg-rose-100 text-rose-700">{{ $queue['late_arrivals'] }}</x-badge>
                        </a>
                    </li>
                </ul>

                <p class="mb-2 mt-5 text-xs font-semibold uppercase tracking-wide text-slate-400">Headcount by Department</p>
                <ul class="space-y-1.5">
                    @foreach ($headcount['by_department'] as $row)
                        <li class="flex items-center justify-between text-sm">
                            <span class="text-slate-600">{{ $row->name }}</span>
                            <span class="font-semibold text-slate-800">{{ $row->total }}</span>
                        </li>
                    @endforeach
                </ul>
            </x-card>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/dashboard.js') }}"></script>
@endpush
