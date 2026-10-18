@extends('layouts.app')
@section('title', 'Positions')

@section('content')
    <x-page-header title="Positions" subtitle="Job titles and their departments.">
        <x-slot:actions>
            <x-button :href="route('hr.positions.create')"><x-icon name="plus" class="h-4 w-4" /> Position</x-button>
        </x-slot:actions>
    </x-page-header>

    <form data-list-filter action="{{ route('hr.positions.index') }}" method="GET"
          class="mb-4 flex flex-wrap items-center gap-3 rounded-2xl border border-slate-200 bg-white p-3 shadow-sm">
        <div class="relative flex-1 min-w-[200px]">
            <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400"><x-icon name="search" class="h-4 w-4" /></span>
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Search title…"
                   class="block w-full rounded-xl border-slate-300 pl-9 text-sm shadow-sm focus:border-accent-500 focus:ring-accent-500">
        </div>
    </form>

    <div data-api-list data-url="{{ route('hr.positions.index') }}">
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Title</th>
                            <th class="px-5 py-3">Department</th>
                            <th class="px-5 py-3">Employees</th>
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
    cols: 4,
    row: function(p) {
        return '<tr class="hover:bg-slate-50/60">' +
            '<td class="px-5 py-3 font-medium text-slate-800">' + HR.esc(p.title) + '</td>' +
            '<td class="px-5 py-3 text-slate-600">' + HR.esc(p.department || '—') + '</td>' +
            '<td class="px-5 py-3 text-slate-600">' + p.employees_count + '</td>' +
            '<td class="px-5 py-3"><div class="flex items-center justify-end gap-1.5">' +
            '<a href="' + p.urls.edit + '" class="rounded-lg px-2.5 py-1 text-xs font-semibold text-accent-600 hover:bg-accent-50">Edit</a>' +
            '<button type="button" data-delete-url="' + p.urls.destroy + '" data-delete-msg="Delete position \'' + HR.esc(p.title) + '\'?"' +
            ' class="rounded-lg px-2.5 py-1 text-xs font-semibold text-rose-600 hover:bg-rose-50">Delete</button>' +
            '</div></td>' +
            '</tr>';
    }
};
</script>
@endpush
