@extends('layouts.app')
@section('title', 'My Payslips')

@section('content')
    <x-page-header title="My Payslips" subtitle="Your finalized pay periods and net pay." />

    <div data-api-list data-url="{{ route('me.payroll.index') }}">
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
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
    draft:      { color: 'bg-slate-100 text-slate-600',    label: 'Draft'     },
    finalized:  { color: 'bg-sky-100 text-sky-700',        label: 'Finalized' },
    paid:       { color: 'bg-emerald-100 text-emerald-700', label: 'Paid'     },
};
window.HR.pageRenderer = {
    cols: 6,
    row: function(p) {
        var st = PAY_STATUS[p.status] || { color: 'bg-slate-100 text-slate-600', label: p.status };
        var period = p.label
            ? HR.esc(p.label)
            : HR.epoch(p.period_start_epoch, 'MMM D') + ' <span class="mx-1">–</span> ' + HR.epoch(p.period_end_epoch, 'MMM D, YYYY');
        return '<tr class="hover:bg-slate-50/60">' +
            '<td class="px-5 py-3 font-medium text-slate-800">' + period + '</td>' +
            '<td class="px-5 py-3 text-slate-600">' + HR.esc(p.gross) + '</td>' +
            '<td class="px-5 py-3 text-slate-600">' + HR.esc(p.deductions_total) + '</td>' +
            '<td class="px-5 py-3 font-bold text-slate-900">' + HR.esc(p.net_pay) + '</td>' +
            '<td class="px-5 py-3">' + HR.badge(st.color, st.label) + '</td>' +
            '<td class="px-5 py-3"><div class="flex items-center justify-end gap-1.5">' +
            '<a href="' + p.urls.show + '" class="rounded-lg px-2.5 py-1 text-xs font-semibold text-accent-600 hover:bg-accent-50">View</a>' +
            '<a href="' + p.urls.payslip + '" target="_blank" class="rounded-lg px-2.5 py-1 text-xs font-semibold text-slate-500 hover:bg-slate-100">PDF</a>' +
            '</div></td>' +
            '</tr>';
    }
};
</script>
@endpush
