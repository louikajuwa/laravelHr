@extends('layouts.app')
@section('title', 'Attendance')

@section('content')
    <x-page-header title="Attendance" subtitle="Daily attendance records, corrections and overtime.">
        <x-slot:actions>
            <x-button variant="secondary" :href="route('hr.attendance.reports', 'daily')"><x-icon name="chart" class="h-4 w-4" /> Reports</x-button>
        </x-slot:actions>
    </x-page-header>

    <form data-list-filter action="{{ route('hr.attendance.index') }}" method="GET"
          class="mb-4 flex flex-wrap items-center gap-3 rounded-2xl border border-slate-200 bg-white p-3 shadow-sm">
        <div class="relative flex-1 min-w-[200px]">
            <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400"><x-icon name="search" class="h-4 w-4" /></span>
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Search name, email or code…"
                   class="block w-full rounded-xl border-slate-300 pl-9 text-sm shadow-sm focus:border-accent-500 focus:ring-accent-500">
        </div>
        <input type="date" name="from" value="{{ $from }}"
               class="rounded-xl border-slate-300 text-sm shadow-sm focus:border-accent-500 focus:ring-accent-500">
        <input type="date" name="to" value="{{ $to }}"
               class="rounded-xl border-slate-300 text-sm shadow-sm focus:border-accent-500 focus:ring-accent-500">
        <select name="department_id" class="rounded-xl border-slate-300 text-sm shadow-sm focus:border-accent-500 focus:ring-accent-500">
            <option value="">All departments</option>
            @foreach ($departments as $d)
                <option value="{{ $d->id }}" @selected(request('department_id') == $d->id)>{{ $d->name }}</option>
            @endforeach
        </select>
        <select name="status" class="rounded-xl border-slate-300 text-sm shadow-sm focus:border-accent-500 focus:ring-accent-500">
            <option value="">All statuses</option>
            <option value="present" @selected(request('status') === 'present')>Present</option>
            <option value="late" @selected(request('status') === 'late')>Late</option>
            <option value="absent" @selected(request('status') === 'absent')>Absent</option>
            <option value="on_leave" @selected(request('status') === 'on_leave')>On Leave</option>
            <option value="half_day" @selected(request('status') === 'half_day')>Half Day</option>
            <option value="holiday" @selected(request('status') === 'holiday')>Holiday</option>
        </select>
    </form>

    <div data-api-list data-url="{{ route('hr.attendance.index') }}">
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Date</th>
                            <th class="px-5 py-3">Employee</th>
                            <th class="px-5 py-3">Time In</th>
                            <th class="px-5 py-3">Time Out</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3">Late</th>
                            <th class="px-5 py-3">OT</th>
                            <th class="px-5 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100"></tbody>
                </table>
            </div>
            <div data-pagination class="hidden border-t border-slate-100 px-5 py-3 flex items-center justify-between gap-4 flex-wrap"></div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
var ATT_STATUS = {
    present:  { color: 'bg-emerald-100 text-emerald-700', label: 'Present'  },
    late:     { color: 'bg-amber-100 text-amber-700',     label: 'Late'     },
    absent:   { color: 'bg-rose-100 text-rose-700',       label: 'Absent'   },
    on_leave: { color: 'bg-sky-100 text-sky-700',         label: 'On Leave' },
    half_day: { color: 'bg-violet-100 text-violet-700',   label: 'Half Day' },
    holiday:  { color: 'bg-indigo-100 text-indigo-700',   label: 'Holiday'  },
};
window.HR.pageRenderer = {
    cols: 8,
    row: function(r) {
        var st = ATT_STATUS[r.status] || { color: 'bg-slate-100 text-slate-600', label: r.status };
        var dept = r.department ? ' · ' + HR.esc(r.department) : '';
        return '<tr class="hover:bg-slate-50/60">' +
            '<td class="px-5 py-3 text-slate-600">' + HR.epoch(r.date_epoch, 'MMM D, YYYY') + '</td>' +
            '<td class="px-5 py-3"><div class="font-medium text-slate-800">' + HR.esc(r.user_name) + '</div>' +
            '<p class="text-xs text-slate-400">' + HR.esc(r.employee_code) + dept + '</p></td>' +
            '<td class="px-5 py-3 text-slate-600">' + HR.epoch(r.time_in_epoch, 'h:mm A') + '</td>' +
            '<td class="px-5 py-3 text-slate-600">' + HR.epoch(r.time_out_epoch, 'h:mm A') + '</td>' +
            '<td class="px-5 py-3">' + HR.badge(st.color, st.label) + '</td>' +
            '<td class="px-5 py-3 text-slate-600">' + (r.late_minutes ? r.late_minutes + 'm' : '—') + '</td>' +
            '<td class="px-5 py-3 text-slate-600">' + (r.overtime_minutes ? r.overtime_minutes + 'm' : '—') + '</td>' +
            '<td class="px-5 py-3"><div class="flex items-center justify-end">' +
            '<a href="' + r.urls.edit + '" class="rounded-lg px-2.5 py-1 text-xs font-semibold text-accent-600 hover:bg-accent-50">Edit</a>' +
            '</div></td>' +
            '</tr>';
    }
};
</script>
@endpush
