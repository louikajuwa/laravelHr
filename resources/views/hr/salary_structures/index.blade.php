@extends('layouts.app')
@section('title', 'Salary Structures')

@section('content')
    <x-page-header title="Salary Structures" subtitle="Define basic pay, overtime and deduction rules per employee.">
        <x-slot:actions>
            <x-button :href="route('hr.salary-structures.create')"><x-icon name="plus" class="h-4 w-4" /> New Structure</x-button>
        </x-slot:actions>
    </x-page-header>

    <form data-list-filter action="{{ route('hr.salary-structures.index') }}" method="GET"
          class="mb-4 flex flex-wrap items-center gap-3 rounded-2xl border border-slate-200 bg-white p-3 shadow-sm">
        <div class="relative flex-1 min-w-[200px]">
            <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400"><x-icon name="search" class="h-4 w-4" /></span>
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Search name or code…"
                   class="block w-full rounded-xl border-slate-300 pl-9 text-sm shadow-sm focus:border-accent-500 focus:ring-accent-500">
        </div>
    </form>

    <div data-api-list data-url="{{ route('hr.salary-structures.index') }}">
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Employee</th>
                            <th class="px-5 py-3">Basic</th>
                            <th class="px-5 py-3">OT ×</th>
                            <th class="px-5 py-3">Late/min</th>
                            <th class="px-5 py-3">Perfect Bonus</th>
                            <th class="px-5 py-3">Effective</th>
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
window.HR.pageRenderer = {
    cols: 7,
    row: function(s) {
        return '<tr class="hover:bg-slate-50/60">' +
            '<td class="px-5 py-3"><p class="font-medium text-slate-800">' + HR.esc(s.user_name) + '</p>' +
            '<p class="text-xs text-slate-400">' + HR.esc(s.employee_code) + '</p></td>' +
            '<td class="px-5 py-3 text-slate-600">' + HR.esc(s.basic_salary) + '</td>' +
            '<td class="px-5 py-3 text-slate-600">' + s.overtime_rate_multiplier + '×</td>' +
            '<td class="px-5 py-3 text-slate-600">' + HR.esc(s.late_deduction_per_minute) + '</td>' +
            '<td class="px-5 py-3 text-slate-600">' + HR.esc(s.perfect_attendance_bonus) + '</td>' +
            '<td class="px-5 py-3 text-slate-600">' + HR.esc(s.effective_from) + '</td>' +
            '<td class="px-5 py-3"><div class="flex items-center justify-end">' +
            '<a href="' + s.urls.edit + '" class="rounded-lg px-2.5 py-1 text-xs font-semibold text-accent-600 hover:bg-accent-50">Edit</a>' +
            '</div></td>' +
            '</tr>';
    }
};
</script>
@endpush
