@extends('layouts.app')
@section('title', 'Shifts')

@section('content')
    <x-page-header title="Shifts" subtitle="Working hours, grace periods and assignments.">
        <x-slot:actions>
            <x-button :href="route('hr.shifts.create')"><x-icon name="plus" class="h-4 w-4" /> Shift</x-button>
        </x-slot:actions>
    </x-page-header>

    <form data-list-filter action="{{ route('hr.shifts.index') }}" method="GET"
          class="mb-4 flex flex-wrap items-center gap-3 rounded-2xl border border-slate-200 bg-white p-3 shadow-sm">
        <div class="relative flex-1 min-w-[200px]">
            <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400"><x-icon name="search" class="h-4 w-4" /></span>
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Search shift name…"
                   class="block w-full rounded-xl border-slate-300 pl-9 text-sm shadow-sm focus:border-accent-500 focus:ring-accent-500">
        </div>
    </form>

    {{-- Shared assign modal (shift ID/name set dynamically by JS) --}}
    <div id="assign-shift-modal" data-modal
         class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-900/50 p-4">
        <div class="w-full max-w-md rounded-2xl bg-white p-5">
            <h3 id="assign-shift-title" class="mb-4 text-base font-semibold text-slate-800">Assign shift to a department</h3>
            <form id="assign-shift-form" method="POST" action="">
                @csrf
                <x-field label="Department" name="department_id">
                    <x-select name="department_id">
                        <option value="">— Select department —</option>
                        @foreach ($departments as $d)
                            <option value="{{ $d->id }}">{{ $d->name }}</option>
                        @endforeach
                    </x-select>
                </x-field>
                <label class="flex items-center gap-2 text-sm mt-3">
                    <input type="checkbox" name="overwrite_users" value="1" class="rounded"> Overwrite each employee's personal shift
                </label>
                <div class="mt-4 flex justify-end gap-2">
                    <button type="button" data-modal-close
                            class="rounded-xl px-4 py-2 text-sm font-semibold text-slate-500 hover:bg-slate-100">Cancel</button>
                    <button type="submit"
                            class="rounded-xl px-4 py-2 text-sm font-semibold text-white bg-accent-600 hover:bg-accent-700">Assign</button>
                </div>
            </form>
        </div>
    </div>

    <div data-api-list data-url="{{ route('hr.shifts.index') }}">
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Name</th>
                            <th class="px-5 py-3">Hours</th>
                            <th class="px-5 py-3">Grace</th>
                            <th class="px-5 py-3">Working days</th>
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
    cols: 6,
    row: function(s) {
        var days = s.working_days.length
            ? s.working_days.map(function(d){ return d.toUpperCase(); }).join(', ')
            : '—';
        return '<tr class="hover:bg-slate-50/60">' +
            '<td class="px-5 py-3 font-medium text-slate-800">' + HR.esc(s.name) + '</td>' +
            '<td class="px-5 py-3 text-slate-600">' + HR.esc(s.start_time) + '–' + HR.esc(s.end_time) + '</td>' +
            '<td class="px-5 py-3 text-slate-600">' + s.grace_period_minutes + 'm</td>' +
            '<td class="px-5 py-3 text-slate-600">' + HR.esc(days) + '</td>' +
            '<td class="px-5 py-3 text-slate-600">' + s.users_count + '</td>' +
            '<td class="px-5 py-3"><div class="flex items-center justify-end gap-1.5">' +
            '<a href="' + s.urls.edit + '" class="rounded-lg px-2.5 py-1 text-xs font-semibold text-accent-600 hover:bg-accent-50">Edit</a>' +
            '<button type="button" data-assign-url="' + s.urls.assign + '" data-shift-name="' + HR.esc(s.name) + '"' +
            ' class="rounded-lg px-2.5 py-1 text-xs font-semibold text-slate-500 hover:bg-slate-100">Assign</button>' +
            '<button type="button" data-delete-url="' + s.urls.destroy + '" data-delete-msg="Delete shift \'' + HR.esc(s.name) + '\'?"' +
            ' class="rounded-lg px-2.5 py-1 text-xs font-semibold text-rose-600 hover:bg-rose-50">Delete</button>' +
            '</div></td>' +
            '</tr>';
    }
};

$(document).on('click', '[data-assign-url]', function() {
    var url = $(this).data('assign-url');
    var name = $(this).data('shift-name');
    $('#assign-shift-title').text('Assign "' + name + '" to a department');
    $('#assign-shift-form').attr('action', url);
    $('#assign-shift-modal').removeClass('hidden').addClass('flex');
});
</script>
@endpush
