@extends('layouts.app')
@section('title', 'Payroll')

@section('content')
    <x-page-header title="Payroll" subtitle="Generate, review and pay employee payslips.">
        <x-slot:actions>
            <x-button data-modal-open="gen-payroll"><x-icon name="plus" class="h-4 w-4" /> Generate Payroll</x-button>
            <x-button variant="secondary" :href="route('hr.payroll.export', request()->query())"><x-icon name="download" class="h-4 w-4" /> Export CSV</x-button>
        </x-slot:actions>
    </x-page-header>

    <div id="gen-payroll" data-modal class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-900/50 p-4">
        <div class="w-full max-w-lg rounded-2xl bg-white p-5">
            <h3 class="mb-4 text-base font-semibold text-slate-800">Generate Payroll</h3>
            <form method="POST" action="{{ route('hr.payroll.generate') }}">
                @csrf
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <x-field label="Period Start" name="period_start" :required="true">
                        <x-input name="period_start" type="date" value="{{ old('period_start', now()->startOfMonth()->format('Y-m-d')) }}" required />
                    </x-field>
                    <x-field label="Period End" name="period_end" :required="true">
                        <x-input name="period_end" type="date" value="{{ old('period_end', now()->endOfMonth()->format('Y-m-d')) }}" required />
                    </x-field>
                    <x-field label="Department" name="department_id" class="sm:col-span-2">
                        <x-select name="department_id">
                            <option value="">All departments</option>
                            @foreach ($departments as $d)
                                <option value="{{ $d->id }}" @selected(old('department_id') == $d->id)>{{ $d->name }}</option>
                            @endforeach
                        </x-select>
                    </x-field>
                </div>
                <div class="mt-5 flex justify-end gap-2">
                    <x-button type="button" variant="ghost" data-modal-close>Cancel</x-button>
                    <x-button type="submit">Generate</x-button>
                </div>
            </form>
        </div>
    </div>

    <form data-list-filter action="{{ route('hr.payroll.index') }}" method="GET"
          class="mb-4 flex flex-wrap items-center gap-3 rounded-2xl border border-slate-200 bg-white p-3 shadow-sm">
        <div class="relative flex-1 min-w-[200px]">
            <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400"><x-icon name="search" class="h-4 w-4" /></span>
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Search name or code…"
                   class="block w-full rounded-xl border-slate-300 pl-9 text-sm shadow-sm focus:border-accent-500 focus:ring-accent-500">
        </div>
        <select name="status" class="rounded-xl border-slate-300 text-sm shadow-sm focus:border-accent-500 focus:ring-accent-500">
            <option value="">All statuses</option>
            <option value="draft" @selected(request('status') === 'draft')>Draft</option>
            <option value="finalized" @selected(request('status') === 'finalized')>Finalized</option>
            <option value="paid" @selected(request('status') === 'paid')>Paid</option>
        </select>
        <input type="date" name="period_start" value="{{ request('period_start') }}"
               class="rounded-xl border-slate-300 text-sm shadow-sm focus:border-accent-500 focus:ring-accent-500">
    </form>

    <div data-api-list data-url="{{ route('hr.payroll.index') }}">
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Employee</th>
                            <th class="px-5 py-3">Period</th>
                            <th class="px-5 py-3">Gross</th>
                            <th class="px-5 py-3">Deductions</th>
                            <th class="px-5 py-3">Net Pay</th>
                            <th class="px-5 py-3">Status</th>
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
var PAY_STATUS = {
    draft:      { color: 'bg-slate-100 text-slate-600',    label: 'Draft'      },
    finalized:  { color: 'bg-sky-100 text-sky-700',        label: 'Finalized'  },
    paid:       { color: 'bg-emerald-100 text-emerald-700', label: 'Paid'      },
};
window.HR.pageRenderer = {
    cols: 7,
    row: function(p) {
        var st = PAY_STATUS[p.status] || { color: 'bg-slate-100 text-slate-600', label: p.status };
        var dept = p.department ? ' · ' + HR.esc(p.department) : '';
        return '<tr class="hover:bg-slate-50/60">' +
            '<td class="px-5 py-3">' +
            '<a href="' + p.urls.show + '" class="font-medium text-slate-800 hover:text-accent-600">' + HR.esc(p.user_name) + '</a>' +
            '<p class="text-xs text-slate-400">' + HR.esc(p.employee_code) + dept + '</p></td>' +
            '<td class="px-5 py-3 text-slate-600">' +
            HR.epoch(p.period_start_epoch, 'MMM D') + ' – ' + HR.epoch(p.period_end_epoch, 'MMM D, YYYY') + '</td>' +
            '<td class="px-5 py-3 text-slate-600">' + HR.esc(p.gross) + '</td>' +
            '<td class="px-5 py-3 text-slate-600">' + HR.esc(p.deductions_total) + '</td>' +
            '<td class="px-5 py-3 font-bold text-slate-800">' + HR.esc(p.net_pay) + '</td>' +
            '<td class="px-5 py-3">' + HR.badge(st.color, st.label) + '</td>' +
            '<td class="px-5 py-3"><div class="flex items-center justify-end gap-1.5">' +
            '<a href="' + p.urls.show + '" class="rounded-lg px-2.5 py-1 text-xs font-semibold text-accent-600 hover:bg-accent-50">View</a>' +
            '<a href="' + p.urls.payslip + '" target="_blank" class="rounded-lg px-2.5 py-1 text-xs font-semibold text-slate-500 hover:bg-slate-100">Payslip</a>' +
            '</div></td>' +
            '</tr>';
    }
};
</script>
@endpush
