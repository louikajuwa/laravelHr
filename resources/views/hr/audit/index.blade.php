@extends('layouts.app')
@section('title', 'Audit Log')

@section('content')
    <x-page-header title="Audit Log" subtitle="System activity and change history." />

    <form data-list-filter action="{{ route('hr.audit.index') }}" method="GET"
          class="mb-4 flex flex-wrap items-center gap-3 rounded-2xl border border-slate-200 bg-white p-3 shadow-sm">
        <select name="action" class="rounded-xl border-slate-300 text-sm shadow-sm focus:border-accent-500 focus:ring-accent-500">
            <option value="">All actions</option>
            @foreach ($actions as $a)
                <option value="{{ $a }}" @selected(request('action') === $a)>{{ $a }}</option>
            @endforeach
        </select>
        <select name="actor_id" class="rounded-xl border-slate-300 text-sm shadow-sm focus:border-accent-500 focus:ring-accent-500">
            <option value="">All actors</option>
            @foreach ($actors as $actor)
                <option value="{{ $actor->id }}" @selected(request('actor_id') == $actor->id)>{{ $actor->name }}</option>
            @endforeach
        </select>
        <input type="date" name="from" value="{{ request('from') }}"
               class="rounded-xl border-slate-300 text-sm shadow-sm focus:border-accent-500 focus:ring-accent-500">
        <input type="date" name="to" value="{{ request('to') }}"
               class="rounded-xl border-slate-300 text-sm shadow-sm focus:border-accent-500 focus:ring-accent-500">
    </form>

    <div data-api-list data-url="{{ route('hr.audit.index') }}">
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Time</th>
                            <th class="px-5 py-3">Actor</th>
                            <th class="px-5 py-3">Action</th>
                            <th class="px-5 py-3">Subject</th>
                            <th class="px-5 py-3">Details</th>
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
    cols: 5,
    row: function(l) {
        var subject = l.subject_type ? HR.esc(l.subject_type + ' #' + l.subject_id) : '—';
        var changes = '';
        if (l.new_values) {
            changes = '<details class="mt-1"><summary class="cursor-pointer text-xs text-accent-600">changes</summary>' +
                '<pre class="mt-1 max-w-md overflow-x-auto rounded bg-slate-50 p-2 text-[11px]">' +
                HR.esc(JSON.stringify(l.new_values, null, 2)) + '</pre></details>';
        }
        return '<tr class="hover:bg-slate-50/60 align-top">' +
            '<td class="px-5 py-3 text-slate-600 whitespace-nowrap">' + HR.epoch(l.created_epoch, 'MMM D, YYYY · h:mm A') + '</td>' +
            '<td class="px-5 py-3 text-slate-700">' + HR.esc(l.actor) + '</td>' +
            '<td class="px-5 py-3">' + HR.badge('bg-slate-100 text-slate-600', HR.esc(l.action)) + '</td>' +
            '<td class="px-5 py-3 text-slate-600">' + subject + '</td>' +
            '<td class="px-5 py-3 text-slate-600"><div>' + HR.esc(l.description || '') + '</div>' + changes + '</td>' +
            '</tr>';
    }
};
</script>
@endpush
