@extends('layouts.app')
@section('title', 'Attendance Reports')

@section('content')
    <x-page-header title="Attendance Reports" :subtitle="$label" />

    @php
        $periods = ['daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly'];
        $qs = array_filter([
            'date' => request('date'),
            'department_id' => request('department_id'),
        ], fn ($v) => $v !== null && $v !== '');
    @endphp

    <div class="mb-4 flex flex-wrap items-center gap-2">
        @foreach ($periods as $key => $name)
            <a href="{{ route('hr.attendance.reports', array_merge(['period' => $key], $qs)) }}" @if ($period === $key) aria-current="page" @endif
               class="rounded-full px-4 py-1.5 text-sm font-semibold transition {{ $period === $key ? 'bg-accent-600 text-white shadow-sm' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' }}">
                {{ $name }}
            </a>
        @endforeach
    </div>

    <form action="{{ route('hr.attendance.reports', $period) }}" method="GET"
          class="mb-4 flex flex-wrap items-end gap-3 rounded-2xl border border-slate-200 bg-white p-3 shadow-sm">
        <x-field label="Date" name="date" class="min-w-[180px]">
            <x-input name="date" type="date" value="{{ request('date') }}" />
        </x-field>
        <x-field label="Department" name="department_id" class="min-w-[200px]">
            <x-select name="department_id">
                <option value="">All departments</option>
                @foreach ($departments as $d)
                    <option value="{{ $d->id }}" @selected(request('department_id') == $d->id)>{{ $d->name }}</option>
                @endforeach
            </x-select>
        </x-field>
        <x-button type="submit" variant="primary">Apply</x-button>
    </form>

    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat label="Present" :value="$summary['present']" icon="check" tone="emerald" />
        <x-stat label="Late" :value="$summary['late']" :sub="$summary['total_late_minutes'].' min'" icon="clock" tone="amber" />
        <x-stat label="Absent" :value="$summary['absent']" icon="x" tone="rose" />
        <x-stat label="Overtime" :value="round($summary['total_overtime_minutes'] / 60, 1).' h'" icon="chart" tone="violet" />
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th scope="col" class="px-5 py-3">Date</th>
                        <th scope="col" class="px-5 py-3">Employee</th>
                        <th scope="col" class="px-5 py-3">Time In</th>
                        <th scope="col" class="px-5 py-3">Time Out</th>
                        <th scope="col" class="px-5 py-3">Status</th>
                        <th scope="col" class="px-5 py-3">Late</th>
                        <th scope="col" class="px-5 py-3">OT</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($records as $r)
                        <tr class="hover:bg-slate-50/60">
                            <td class="px-5 py-3 text-slate-600">
                                <span data-epoch="{{ $r->date->timestamp }}" data-format="MMM D, YYYY">{{ $r->date->format('M d, Y') }}</span>
                            </td>
                            <td class="px-5 py-3">
                                <div class="font-medium text-slate-800">{{ $r->user->name }}</div>
                                <p class="text-xs text-slate-500">{{ $r->user->employee_code }}@if($r->user->department) · {{ $r->user->department->name }}@endif</p>
                            </td>
                            <td class="px-5 py-3 text-slate-600">
                                @if ($r->time_in)
                                    <span data-epoch="{{ $r->epoch('time_in') }}" data-format="h:mm A">—</span>
                                @else — @endif
                            </td>
                            <td class="px-5 py-3 text-slate-600">
                                @if ($r->time_out)
                                    <span data-epoch="{{ $r->epoch('time_out') }}" data-format="h:mm A">—</span>
                                @else — @endif
                            </td>
                            <td class="px-5 py-3">
                                <x-badge :color="\App\Enums\AttendanceStatus::badgeFor($r->status)">{{ \App\Enums\AttendanceStatus::labelFor($r->status) }}</x-badge>
                            </td>
                            <td class="px-5 py-3 text-slate-600">{{ $r->late_minutes ? $r->late_minutes.'m' : '—' }}</td>
                            <td class="px-5 py-3 text-slate-600">{{ $r->overtime_minutes ? $r->overtime_minutes.'m' : '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><x-empty message="No attendance records for this period." icon="clock" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($records->hasPages())
            <div class="border-t border-slate-100 px-5 py-3">{{ $records->links() }}</div>
        @endif
    </div>
@endsection
