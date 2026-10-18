@extends('layouts.app')
@section('title', 'Leave Requests')

@section('content')
    <x-page-header title="Leave Requests" subtitle="Review and decide on employee leave requests." />

    <form data-list-filter action="{{ route('hr.leave.index') }}" method="GET"
          class="mb-4 flex flex-wrap items-center gap-3 rounded-2xl border border-slate-200 bg-white p-3 shadow-sm">
        <div class="relative flex-1 min-w-[200px]">
            <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-500"><x-icon name="search" class="h-4 w-4" /></span>
            <input type="search" name="q" aria-label="Search name, email or code" value="{{ request('q') }}" placeholder="Search name, email or code…"
                   class="block w-full rounded-xl border-slate-300 pl-9 text-sm shadow-sm focus:border-accent-500 focus:ring-accent-500">
        </div>
        <select name="status" aria-label="Status" class="rounded-xl border-slate-300 text-sm shadow-sm focus:border-accent-500 focus:ring-accent-500">
            <option value="">All statuses</option>
            <option value="pending" @selected(request('status') === 'pending')>Pending</option>
            <option value="approved" @selected(request('status') === 'approved')>Approved</option>
            <option value="rejected" @selected(request('status') === 'rejected')>Rejected</option>
            <option value="cancelled" @selected(request('status') === 'cancelled')>Cancelled</option>
            <option value="revoked" @selected(request('status') === 'revoked')>Revoked</option>
        </select>
        <select name="leave_type_id" aria-label="Leave type" class="rounded-xl border-slate-300 text-sm shadow-sm focus:border-accent-500 focus:ring-accent-500">
            <option value="">All types</option>
            @foreach ($types as $t)
                <option value="{{ $t->id }}" @selected(request('leave_type_id') == $t->id)>{{ $t->name }}</option>
            @endforeach
        </select>
    </form>

    {{-- Shared approve modal --}}
    <div id="approve-modal" data-modal role="dialog" aria-modal="true" aria-labelledby="approve-modal-title" aria-describedby="approve-modal-subtitle" class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-900/50 p-4">
        <div class="w-full max-w-md rounded-2xl bg-white p-5 text-left">
            <h3 id="approve-modal-title" class="text-base font-semibold text-slate-800">Approve leave request</h3>
            <p id="approve-modal-subtitle" class="mt-1 text-sm text-slate-500"></p>
            <form id="approve-modal-form" method="POST" action="" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label for="approve-decision-note" class="block text-sm font-medium text-slate-700 mb-1">Decision note</label>
                    <textarea id="approve-decision-note" name="decision_note" rows="3"
                              class="block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-accent-500 focus:ring-accent-500"></textarea>
                </div>
                <div class="flex items-center justify-end gap-2">
                    <button type="button" data-modal-close
                            class="rounded-xl px-3 py-1.5 text-sm font-semibold text-slate-600 hover:bg-slate-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent-500">Cancel</button>
                    <button type="submit"
                            class="rounded-xl px-3 py-1.5 text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent-500 focus-visible:ring-offset-2">Approve</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Shared reject modal --}}
    <div id="reject-modal" data-modal role="dialog" aria-modal="true" aria-labelledby="reject-modal-title" aria-describedby="reject-modal-subtitle" class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-900/50 p-4">
        <div class="w-full max-w-md rounded-2xl bg-white p-5 text-left">
            <h3 id="reject-modal-title" class="text-base font-semibold text-slate-800">Reject leave request</h3>
            <p id="reject-modal-subtitle" class="mt-1 text-sm text-slate-500"></p>
            <form id="reject-modal-form" method="POST" action="" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label for="reject-decision-note" class="block text-sm font-medium text-slate-700 mb-1">Decision note</label>
                    <textarea id="reject-decision-note" name="decision_note" rows="3"
                              class="block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-accent-500 focus:ring-accent-500"></textarea>
                </div>
                <div class="flex items-center justify-end gap-2">
                    <button type="button" data-modal-close
                            class="rounded-xl px-3 py-1.5 text-sm font-semibold text-slate-600 hover:bg-slate-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent-500">Cancel</button>
                    <button type="submit"
                            class="rounded-xl px-3 py-1.5 text-sm font-semibold text-white bg-rose-600 hover:bg-rose-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent-500 focus-visible:ring-offset-2">Reject</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Shared revoke modal --}}
    <div id="revoke-modal" data-modal role="dialog" aria-modal="true" aria-labelledby="revoke-modal-title" aria-describedby="revoke-modal-subtitle" class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-900/50 p-4">
        <div class="w-full max-w-md rounded-2xl bg-white p-5 text-left">
            <h3 id="revoke-modal-title" class="text-base font-semibold text-slate-800">Revoke approved leave</h3>
            <p id="revoke-modal-subtitle" class="mt-1 text-sm text-slate-500"></p>
            <p class="mt-2 rounded-xl bg-amber-50 px-3 py-2 text-sm text-amber-800">Balance will be refunded; draft payrolls covering these dates will be discarded.</p>
            <form id="revoke-modal-form" method="POST" action="" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label for="revoke-note" class="block text-sm font-medium text-slate-700 mb-1">Reason</label>
                    <textarea id="revoke-note" name="note" rows="3"
                              class="block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-accent-500 focus:ring-accent-500"></textarea>
                </div>
                <div class="flex items-center justify-end gap-2">
                    <button type="button" data-modal-close
                            class="rounded-xl px-3 py-1.5 text-sm font-semibold text-slate-600 hover:bg-slate-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent-500">Cancel</button>
                    <button type="submit"
                            class="rounded-xl px-3 py-1.5 text-sm font-semibold text-white bg-rose-600 hover:bg-rose-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent-500 focus-visible:ring-offset-2">Revoke</button>
                </div>
            </form>
        </div>
    </div>

    <div data-api-list data-url="{{ route('hr.leave.index') }}">
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th scope="col" class="px-5 py-3">Employee</th>
                            <th scope="col" class="px-5 py-3">Type</th>
                            <th scope="col" class="px-5 py-3">Dates</th>
                            <th scope="col" class="px-5 py-3">Days</th>
                            <th scope="col" class="px-5 py-3">Status</th>
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
var LEAVE_STATUS = {
    pending:   { color: 'bg-amber-100 text-amber-700',   label: 'Pending'   },
    approved:  { color: 'bg-emerald-100 text-emerald-700', label: 'Approved' },
    rejected:  { color: 'bg-rose-100 text-rose-700',     label: 'Rejected'  },
    cancelled: { color: 'bg-slate-200 text-slate-600',   label: 'Cancelled' },
    revoked:   { color: 'bg-orange-100 text-orange-700', label: 'Revoked'   },
};
window.HR.pageRenderer = {
    cols: 6,
    row: function(r) {
        var st = LEAVE_STATUS[r.status] || { color: 'bg-slate-100 text-slate-600', label: r.status };
        var dept = r.department ? ' · ' + HR.esc(r.department) : '';
        var actions = '';
        if (r.status === 'pending') {
            actions =
                '<button type="button" data-approve-url="' + r.urls.approve + '"' +
                ' data-leave-subtitle="' + HR.esc(r.user_name + ' · ' + r.leave_type + ' · ' + r.days + ' day(s)') + '"' +
                ' class="rounded-lg px-2.5 py-1 text-xs font-semibold focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent-500 text-emerald-700 hover:bg-emerald-50">Approve</button>' +
                '<button type="button" data-reject-url="' + r.urls.reject + '"' +
                ' data-leave-subtitle="' + HR.esc(r.user_name + ' · ' + r.leave_type + ' · ' + r.days + ' day(s)') + '"' +
                ' class="rounded-lg px-2.5 py-1 text-xs font-semibold focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent-500 text-rose-600 hover:bg-rose-50">Reject</button>';
        } else if (r.status === 'approved' && r.urls.revoke) {
            actions =
                '<button type="button" data-revoke-url="' + r.urls.revoke + '"' +
                ' data-leave-subtitle="' + HR.esc(r.user_name + ' · ' + r.leave_type + ' · ' + r.days + ' day(s)') + '"' +
                ' class="rounded-lg px-2.5 py-1 text-xs font-semibold focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent-500 text-rose-600 hover:bg-rose-50">Revoke</button>';
        } else if (r.decision_note) {
            actions = '<span class="text-xs text-slate-500">' + HR.esc(r.decision_note) + '</span>';
        } else {
            actions = '<span class="text-slate-500">—</span>';
        }
        return '<tr class="hover:bg-slate-50/60">' +
            '<td class="px-5 py-3"><div class="font-medium text-slate-800">' + HR.esc(r.user_name) + '</div>' +
            '<p class="text-xs text-slate-500">' + HR.esc(r.employee_code) + dept + '</p></td>' +
            '<td class="px-5 py-3 text-slate-600">' + HR.esc(r.leave_type) + '</td>' +
            '<td class="px-5 py-3 text-slate-600">' +
            HR.epoch(r.start_date_epoch, 'MMM D') + ' <span class="mx-1">–</span> ' + HR.epoch(r.end_date_epoch, 'MMM D, YYYY') +
            '</td>' +
            '<td class="px-5 py-3 text-slate-600">' + r.days + '</td>' +
            '<td class="px-5 py-3">' + HR.badge(st.color, st.label) + '</td>' +
            '<td class="px-5 py-3"><div class="flex items-center justify-end gap-1.5">' + actions + '</div></td>' +
            '</tr>';
    }
};

$(document).on('click', '[data-approve-url]', function() {
    var url = $(this).data('approve-url');
    var subtitle = $(this).data('leave-subtitle');
    $('#approve-modal-subtitle').text(subtitle);
    $('#approve-modal-form').attr('action', url).find('textarea').val('');
    HR.openModal('#approve-modal', this);
});
$(document).on('click', '[data-reject-url]', function() {
    var url = $(this).data('reject-url');
    var subtitle = $(this).data('leave-subtitle');
    $('#reject-modal-subtitle').text(subtitle);
    $('#reject-modal-form').attr('action', url).find('textarea').val('');
    HR.openModal('#reject-modal', this);
});
$(document).on('click', '[data-revoke-url]', function() {
    $('#revoke-modal-subtitle').text($(this).data('leave-subtitle'));
    $('#revoke-modal-form').attr('action', $(this).data('revoke-url')).find('textarea').val('');
    HR.openModal('#revoke-modal', this);
});
</script>
@endpush
