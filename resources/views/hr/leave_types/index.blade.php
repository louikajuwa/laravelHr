@extends('layouts.app')
@section('title', 'Leave Types')

@section('content')
    <x-page-header title="Leave Types" subtitle="Categories of leave, balances and approval rules.">
        <x-slot:actions>
            <x-button :href="route('hr.leave-types.create')"><x-icon name="plus" class="h-4 w-4" /> Leave Type</x-button>
        </x-slot:actions>
    </x-page-header>

    <form data-list-filter action="{{ route('hr.leave-types.index') }}" method="GET"
          class="mb-4 flex flex-wrap items-center gap-3 rounded-2xl border border-slate-200 bg-white p-3 shadow-sm">
        <div class="relative flex-1 min-w-[200px]">
            <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-500"><x-icon name="search" class="h-4 w-4" /></span>
            <input type="search" name="q" aria-label="Search name or code" value="{{ request('q') }}" placeholder="Search name or code…"
                   class="block w-full rounded-xl border-slate-300 pl-9 text-sm shadow-sm focus:border-accent-500 focus:ring-accent-500">
        </div>
    </form>

    <div data-api-list data-url="{{ route('hr.leave-types.index') }}">
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th scope="col" class="px-5 py-3">Name</th>
                            <th scope="col" class="px-5 py-3">Code</th>
                            <th scope="col" class="px-5 py-3">Days/yr</th>
                            <th scope="col" class="px-5 py-3">Paid</th>
                            <th scope="col" class="px-5 py-3">Active</th>
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
window.HR.pageRenderer = {
    cols: 6,
    row: function(t) {
        var paidBadge   = t.is_paid   ? HR.badge('bg-emerald-100 text-emerald-700','Yes') : HR.badge('bg-slate-200 text-slate-600','No');
        var activeBadge = t.is_active ? HR.badge('bg-emerald-100 text-emerald-700','Active') : HR.badge('bg-slate-200 text-slate-600','Inactive');
        return '<tr class="hover:bg-slate-50/60">' +
            '<td class="px-5 py-3 font-medium text-slate-800">' + HR.esc(t.name) + '</td>' +
            '<td class="px-5 py-3">' + HR.badge('bg-slate-100 text-slate-600', t.code) + '</td>' +
            '<td class="px-5 py-3 text-slate-600">' + t.default_days_per_year + '</td>' +
            '<td class="px-5 py-3">' + paidBadge + '</td>' +
            '<td class="px-5 py-3">' + activeBadge + '</td>' +
            '<td class="px-5 py-3"><div class="flex items-center justify-end gap-1.5">' +
            '<a href="' + t.urls.edit + '" class="rounded-lg px-2.5 py-1 text-xs font-semibold focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent-500 text-accent-600 hover:bg-accent-50">Edit</a>' +
            '<button type="button" data-delete-url="' + t.urls.destroy + '" data-delete-msg="Delete leave type \'' + HR.esc(t.name) + '\'?"' +
            ' class="rounded-lg px-2.5 py-1 text-xs font-semibold focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent-500 text-rose-600 hover:bg-rose-50">Delete</button>' +
            '</div></td>' +
            '</tr>';
    }
};
</script>
@endpush
