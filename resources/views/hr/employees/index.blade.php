@extends('layouts.app')
@section('title', 'Employees')

@section('content')
    <x-page-header title="Employees" subtitle="Manage staff, roles and department assignments.">
        <x-slot:actions>
            <x-button :href="route('hr.employees.create')"><x-icon name="plus" class="h-4 w-4" /> Add Employee</x-button>
        </x-slot:actions>
    </x-page-header>

    <form data-list-filter action="{{ route('hr.employees.index') }}" method="GET"
          class="mb-4 flex flex-wrap items-center gap-3 rounded-2xl border border-slate-200 bg-white p-3 shadow-sm">
        <div class="relative flex-1 min-w-[200px]">
            <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400"><x-icon name="search" class="h-4 w-4" /></span>
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Search name, email or code…"
                   class="block w-full rounded-xl border-slate-300 pl-9 text-sm shadow-sm focus:border-accent-500 focus:ring-accent-500">
        </div>
        <select name="department_id" class="rounded-xl border-slate-300 text-sm shadow-sm focus:border-accent-500 focus:ring-accent-500">
            <option value="">All departments</option>
            @foreach ($departments as $d)
                <option value="{{ $d->id }}" @selected(request('department_id') == $d->id)>{{ $d->name }}</option>
            @endforeach
        </select>
        <select name="status" class="rounded-xl border-slate-300 text-sm shadow-sm focus:border-accent-500 focus:ring-accent-500">
            <option value="">All statuses</option>
            <option value="active" @selected(request('status') === 'active')>Active</option>
            <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
        </select>
    </form>

    <div data-api-list data-url="{{ route('hr.employees.index') }}">
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Employee</th>
                            <th class="px-5 py-3">Department</th>
                            <th class="px-5 py-3">Position</th>
                            <th class="px-5 py-3">Role</th>
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
window.HR.pageRenderer = {
    cols: 6,
    row: function(e) {
        var statusBadge = e.is_active
            ? HR.badge('bg-emerald-100 text-emerald-700', 'Active')
            : HR.badge('bg-slate-200 text-slate-600', 'Inactive');
        var roleBadge = e.roles.length
            ? e.roles.map(function(r){ return HR.badge('bg-slate-100 text-slate-600', r.charAt(0).toUpperCase() + r.slice(1)); }).join(' ')
            : '—';
        var confirmMsg = encodeURIComponent('Change active status for ' + e.name + '?');
        return '<tr class="hover:bg-slate-50/60">' +
            '<td class="px-5 py-3"><div class="flex items-center gap-3">' +
            '<span class="flex h-9 w-9 items-center justify-center rounded-full bg-accent-100 text-xs font-bold text-accent-700">' + HR.esc(e.initials) + '</span>' +
            '<div><a href="' + e.urls.show + '" class="font-medium text-slate-800 hover:text-accent-600">' + HR.esc(e.name) + '</a>' +
            '<p class="text-xs text-slate-400">' + HR.esc(e.employee_code) + ' · ' + HR.esc(e.email) + '</p></div>' +
            '</div></td>' +
            '<td class="px-5 py-3 text-slate-600">' + HR.esc(e.department || '—') + '</td>' +
            '<td class="px-5 py-3 text-slate-600">' + HR.esc(e.job_position || '—') + '</td>' +
            '<td class="px-5 py-3">' + roleBadge + '</td>' +
            '<td class="px-5 py-3">' + statusBadge + '</td>' +
            '<td class="px-5 py-3"><div class="flex items-center justify-end gap-1.5">' +
            '<a href="' + e.urls.edit + '" class="rounded-lg px-2.5 py-1 text-xs font-semibold text-accent-600 hover:bg-accent-50">Edit</a>' +
            '<button type="button" data-toggle-url="' + e.urls.toggle + '" data-toggle-name="' + HR.esc(e.name) + '" data-is-active="' + e.is_active + '"' +
            ' class="rounded-lg px-2.5 py-1 text-xs font-semibold text-slate-500 hover:bg-slate-100">' +
            (e.is_active ? 'Deactivate' : 'Activate') + '</button>' +
            '</div></td>' +
            '</tr>';
    }
};

$(document).on('click', '[data-toggle-url]', function() {
    var $btn = $(this);
    var name = $btn.data('toggle-name');
    var isActive = $btn.data('is-active');
    HR.confirm({
        message: (isActive ? 'Deactivate ' : 'Activate ') + name + '?',
        confirmText: isActive ? 'Deactivate' : 'Activate',
        confirmClass: isActive ? 'bg-rose-600 hover:bg-rose-700' : 'bg-emerald-600 hover:bg-emerald-700',
        onConfirm: function() {
            var $form = $('<form method="POST" action="' + $btn.data('toggle-url') + '" style="display:none">');
            $form.append('<input type="hidden" name="_token" value="' + $('meta[name=csrf-token]').attr('content') + '">');
            $('body').append($form);
            $form.submit();
        }
    });
});
</script>
@endpush
