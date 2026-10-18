@extends('layouts.app')
@section('title', 'My Leave')

@section('content')
    <x-page-header title="My Leave" subtitle="Track your leave requests and their status.">
        <x-slot:actions>
            <x-button :href="route('me.leave.create')"><x-icon name="plus" class="h-4 w-4" /> Request Leave</x-button>
        </x-slot:actions>
    </x-page-header>

    <form data-list-filter action="{{ route('me.leave.index') }}" method="GET"
          class="mb-4 flex flex-wrap items-center gap-3 rounded-2xl border border-slate-200 bg-white p-3 shadow-sm">
        <select name="status" class="rounded-xl border-slate-300 text-sm shadow-sm focus:border-accent-500 focus:ring-accent-500">
            <option value="">All statuses</option>
            <option value="pending" @selected(request('status') === 'pending')>Pending</option>
            <option value="approved" @selected(request('status') === 'approved')>Approved</option>
            <option value="rejected" @selected(request('status') === 'rejected')>Rejected</option>
            <option value="cancelled" @selected(request('status') === 'cancelled')>Cancelled</option>
        </select>
    </form>

    <div data-api-list data-url="{{ route('me.leave.index') }}">
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Type</th>
                            <th class="px-5 py-3">Dates</th>
                            <th class="px-5 py-3">Days</th>
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
var LEAVE_STATUS = {
    pending:   { color: 'bg-amber-100 text-amber-700',    label: 'Pending'   },
    approved:  { color: 'bg-emerald-100 text-emerald-700', label: 'Approved' },
    rejected:  { color: 'bg-rose-100 text-rose-700',      label: 'Rejected'  },
    cancelled: { color: 'bg-slate-200 text-slate-600',    label: 'Cancelled' },
};
window.HR.pageRenderer = {
    cols: 5,
    row: function(r) {
        var st = LEAVE_STATUS[r.status] || { color: 'bg-slate-100 text-slate-600', label: r.status };
        var reason = r.reason ? '<p class="text-xs text-slate-400">' + HR.esc(r.reason.length > 60 ? r.reason.slice(0,60) + '…' : r.reason) + '</p>' : '';
        var actions = r.status === 'pending'
            ? '<button type="button" data-cancel-url="' + r.urls.cancel + '"' +
              ' class="rounded-lg px-2.5 py-1 text-xs font-semibold text-rose-600 hover:bg-rose-50">Cancel</button>'
            : '<span class="text-slate-400">—</span>';
        return '<tr class="hover:bg-slate-50/60">' +
            '<td class="px-5 py-3"><div class="font-medium text-slate-800">' + HR.esc(r.leave_type) + '</div>' + reason + '</td>' +
            '<td class="px-5 py-3 text-slate-600">' +
            HR.epoch(r.start_date_epoch, 'MMM D') + ' <span class="mx-1">–</span> ' + HR.epoch(r.end_date_epoch, 'MMM D, YYYY') + '</td>' +
            '<td class="px-5 py-3 text-slate-600">' + r.days + '</td>' +
            '<td class="px-5 py-3">' + HR.badge(st.color, st.label) + '</td>' +
            '<td class="px-5 py-3"><div class="flex items-center justify-end">' + actions + '</div></td>' +
            '</tr>';
    }
};

$(document).on('click', '[data-cancel-url]', function() {
    var url = $(this).data('cancel-url');
    HR.confirm({
        message: 'Cancel this leave request?',
        confirmText: 'Cancel Request',
        confirmClass: 'bg-rose-600 hover:bg-rose-700',
        onConfirm: function() {
            var $form = $('<form method="POST" action="' + url + '" style="display:none">');
            $form.append('<input type="hidden" name="_token" value="' + $('meta[name=csrf-token]').attr('content') + '">');
            $('body').append($form);
            $form.submit();
        }
    });
});
</script>
@endpush
