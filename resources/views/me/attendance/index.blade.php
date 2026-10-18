@extends('layouts.app')
@section('title', 'My Attendance')

@section('content')
    <x-page-header title="My Attendance" subtitle="Your daily clock-in records and time totals.">
        <x-slot:actions>
            <x-button variant="secondary" :href="route('me.late-arrivals.create')"><x-icon name="alert" class="h-4 w-4" /> Report Late</x-button>
        </x-slot:actions>
    </x-page-header>

    <form data-list-filter action="{{ route('me.attendance.index') }}" method="GET"
          class="mb-4 flex flex-wrap items-center gap-3 rounded-2xl border border-slate-200 bg-white p-3 shadow-sm">
        <input type="month" name="month" aria-label="Month" value="{{ $month }}"
               class="rounded-xl border-slate-300 text-sm shadow-sm focus:border-accent-500 focus:ring-accent-500">
    </form>

    <div data-api-list data-url="{{ route('me.attendance.index') }}">
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th scope="col" class="px-5 py-3">Date</th>
                            <th scope="col" class="px-5 py-3">Time In</th>
                            <th scope="col" class="px-5 py-3">Time Out</th>
                            <th scope="col" class="px-5 py-3">Status</th>
                            <th scope="col" class="px-5 py-3">Late</th>
                            <th scope="col" class="px-5 py-3">OT</th>
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
    cols: 6,
    row: function(r) {
        var st = ATT_STATUS[r.status] || { color: 'bg-slate-100 text-slate-600', label: r.status };
        return '<tr class="hover:bg-slate-50/60">' +
            '<td class="px-5 py-3 font-medium text-slate-800">' + HR.epoch(r.date_epoch, 'ddd, MMM D') + '</td>' +
            '<td class="px-5 py-3 text-slate-600">' + HR.epoch(r.time_in_epoch, 'h:mm A') + '</td>' +
            '<td class="px-5 py-3 text-slate-600">' + HR.epoch(r.time_out_epoch, 'h:mm A') + '</td>' +
            '<td class="px-5 py-3">' + HR.badge(st.color, st.label) + '</td>' +
            '<td class="px-5 py-3 text-slate-600">' + (r.late_minutes ? r.late_minutes + 'm' : '—') + '</td>' +
            '<td class="px-5 py-3 text-slate-600">' + (r.overtime_minutes ? r.overtime_minutes + 'm' : '—') + '</td>' +
            '</tr>';
    }
};
</script>
@endpush
