@extends('layouts.app')
@section('title', 'Departments')

@section('content')
    <x-page-header title="Departments" subtitle="Organise teams, managers and default shifts.">
        <x-slot:actions>
            <x-button :href="route('hr.departments.create')"><x-icon name="plus" class="h-4 w-4" /> Department</x-button>
        </x-slot:actions>
    </x-page-header>

    <form data-list-filter action="{{ route('hr.departments.index') }}" method="GET"
          class="mb-4 flex flex-wrap items-center gap-3 rounded-2xl border border-slate-200 bg-white p-3 shadow-sm">
        <div class="relative flex-1 min-w-[200px]">
            <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-500"><x-icon name="search" class="h-4 w-4" /></span>
            <input type="search" name="q" aria-label="Search name or code" value="{{ request('q') }}" placeholder="Search name or code…"
                   class="block w-full rounded-xl border-slate-300 pl-9 text-sm shadow-sm focus:border-accent-500 focus:ring-accent-500">
        </div>
    </form>

    <div data-api-list data-url="{{ route('hr.departments.index') }}">
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th scope="col" class="px-5 py-3">Name</th>
                            <th scope="col" class="px-5 py-3">Manager</th>
                            <th scope="col" class="px-5 py-3">Shift</th>
                            <th scope="col" class="px-5 py-3">Employees</th>
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
window.HR.pageRenderer = {
    cols: 6,
    row: function(d) {
        var statusBadge = d.is_active
            ? HR.badge('bg-emerald-100 text-emerald-700', 'Active')
            : HR.badge('bg-slate-200 text-slate-600', 'Inactive');
        return '<tr class="hover:bg-slate-50/60">' +
            '<td class="px-5 py-3"><p class="font-medium text-slate-800">' + HR.esc(d.name) + '</p>' +
            '<p class="text-xs text-slate-500">' + HR.esc(d.code || '—') + '</p></td>' +
            '<td class="px-5 py-3 text-slate-600">' + HR.esc(d.manager || '—') + '</td>' +
            '<td class="px-5 py-3 text-slate-600">' + HR.esc(d.shift || '—') + '</td>' +
            '<td class="px-5 py-3 text-slate-600">' + d.employees_count + '</td>' +
            '<td class="px-5 py-3">' + statusBadge + '</td>' +
            '<td class="px-5 py-3"><div class="flex items-center justify-end gap-1.5">' +
            '<a href="' + d.urls.edit + '" class="rounded-lg px-2.5 py-1 text-xs font-semibold focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent-500 text-accent-600 hover:bg-accent-50">Edit</a>' +
            '<button type="button" data-delete-url="' + d.urls.destroy + '" data-delete-msg="Delete department \'' + HR.esc(d.name) + '\'?"' +
            ' class="rounded-lg px-2.5 py-1 text-xs font-semibold focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent-500 text-rose-600 hover:bg-rose-50">Delete</button>' +
            '</div></td>' +
            '</tr>';
    }
};
</script>
@endpush
