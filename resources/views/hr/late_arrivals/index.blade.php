@extends('layouts.app')
@section('title', 'Late Arrivals')

@section('content')
    <x-page-header title="Late Arrivals" subtitle="Review and decide on late arrival reports." />

    <form data-list-filter action="{{ route('hr.late-arrivals.index') }}" method="GET"
          class="mb-4 flex flex-wrap items-center gap-3 rounded-2xl border border-slate-200 bg-white p-3 shadow-sm">
        <select name="status" aria-label="Status" class="rounded-xl border-slate-300 text-sm shadow-sm focus:border-accent-500 focus:ring-accent-500">
            <option value="">All statuses</option>
            <option value="pending" @selected(request('status') === 'pending')>Pending</option>
            <option value="accepted" @selected(request('status') === 'accepted')>Accepted</option>
            <option value="excused" @selected(request('status') === 'excused')>Excused</option>
            <option value="rejected" @selected(request('status') === 'rejected')>Rejected</option>
        </select>
    </form>

    <div data-api-list data-url="{{ route('hr.late-arrivals.index') }}">
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th scope="col" class="px-5 py-3">Date</th>
                            <th scope="col" class="px-5 py-3">Employee</th>
                            <th scope="col" class="px-5 py-3">Late</th>
                            <th scope="col" class="px-5 py-3">Reason</th>
                            <th scope="col" class="px-5 py-3">Status</th>
                            <th scope="col" class="px-5 py-3">Reviewed By</th>
                            <th scope="col" class="px-5 py-3 text-right">Actions</th>
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
var LATE_STATUS = {
    pending:  { color: 'bg-amber-100 text-amber-700',   label: 'Pending'  },
    accepted: { color: 'bg-emerald-100 text-emerald-700', label: 'Accepted' },
    excused:  { color: 'bg-sky-100 text-sky-700',       label: 'Excused'  },
    rejected: { color: 'bg-rose-100 text-rose-700',     label: 'Rejected' },
};
window.HR.pageRenderer = {
    cols: 7,
    row: function(r) {
        var st = LATE_STATUS[r.status] || { color: 'bg-slate-100 text-slate-600', label: r.status };
        var reason = r.reason ? HR.esc(r.reason.length > 60 ? r.reason.slice(0, 60) + '…' : r.reason) : '—';
        var actions = '';
        if (r.status === 'pending') {
            actions =
                '<button type="button" data-review-url="' + r.urls.review + '" data-review-status="accepted"' +
                ' data-review-msg="Accept this late arrival?" class="rounded-lg px-2.5 py-1 text-xs font-semibold focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent-500 text-emerald-700 hover:bg-emerald-50">Accept</button>' +
                '<button type="button" data-review-url="' + r.urls.review + '" data-review-status="excused"' +
                ' data-review-msg="Mark as excused?" class="rounded-lg px-2.5 py-1 text-xs font-semibold focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent-500 text-slate-500 hover:bg-slate-100">Excuse</button>' +
                '<button type="button" data-review-url="' + r.urls.review + '" data-review-status="rejected"' +
                ' data-review-msg="Reject this late arrival report?" class="rounded-lg px-2.5 py-1 text-xs font-semibold focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent-500 text-rose-600 hover:bg-rose-50">Reject</button>';
        } else {
            actions = '<span class="text-slate-500">—</span>';
        }
        return '<tr class="hover:bg-slate-50/60">' +
            '<td class="px-5 py-3 text-slate-600">' + HR.epoch(r.date_epoch, 'MMM D, YYYY') + '</td>' +
            '<td class="px-5 py-3"><div class="font-medium text-slate-800">' + HR.esc(r.user_name) + '</div>' +
            '<p class="text-xs text-slate-500">' + HR.esc(r.employee_code) + '</p></td>' +
            '<td class="px-5 py-3 text-slate-600">' + r.late_minutes + 'm</td>' +
            '<td class="px-5 py-3 text-slate-600">' + reason + '</td>' +
            '<td class="px-5 py-3">' + HR.badge(st.color, st.label) + '</td>' +
            '<td class="px-5 py-3 text-slate-600">' + HR.esc(r.reviewer || '—') + '</td>' +
            '<td class="px-5 py-3"><div class="flex items-center justify-end gap-1.5">' + actions + '</div></td>' +
            '</tr>';
    }
};
</script>
@endpush
