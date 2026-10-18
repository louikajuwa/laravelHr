@extends('layouts.app')
@section('title', 'Request Leave')

@section('content')
    <x-page-header title="Request Leave" subtitle="Submit a new leave request for HR approval." />

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <x-card>
                <form method="POST" action="{{ route('me.leave.store') }}">
                    @csrf
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <x-field label="Leave Type" name="leave_type_id" :required="true" class="sm:col-span-2">
                            <x-select name="leave_type_id">
                                <option value="">— Select leave type —</option>
                                @foreach ($types as $t)
                                    <option value="{{ $t->id }}" @selected(old('leave_type_id') == $t->id)>{{ $t->name }}</option>
                                @endforeach
                            </x-select>
                            <p id="bal-note" role="status" aria-live="polite" class="mt-1 text-xs text-slate-500"></p>
                        </x-field>

                        <x-field label="Start Date" name="start_date" :required="true">
                            <x-input name="start_date" type="date" value="{{ old('start_date') }}" required />
                        </x-field>
                        <x-field label="End Date" name="end_date" :required="true">
                            <x-input name="end_date" type="date" value="{{ old('end_date') }}" required />
                        </x-field>

                        <x-field label="Reason" name="reason" class="sm:col-span-2">
                            <x-textarea name="reason">{{ old('reason') }}</x-textarea>
                        </x-field>
                    </div>

                    <div class="mt-6 flex items-center justify-end gap-2">
                        <x-button variant="secondary" :href="route('me.leave.index')">Cancel</x-button>
                        <x-button type="submit">Submit Request</x-button>
                    </div>
                </form>
            </x-card>
        </div>

        <div>
            <x-card title="Available Balances" subtitle="Remaining days per leave type.">
                <div class="space-y-3">
                    @forelse ($types as $t)
                        @php $bal = $balances[$t->id] ?? null; @endphp
                        <div class="flex items-center justify-between rounded-xl border border-slate-200 px-3 py-2">
                            <div>
                                <p class="text-sm font-medium text-slate-800">{{ $t->name }}</p>
                                <p class="text-xs text-slate-500">{{ $t->is_paid ? 'Paid' : 'Unpaid' }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-bold text-slate-900">{{ $bal ? number_format($bal->available, 1) : '0.0' }}</p>
                                <p class="text-[11px] text-slate-500">of {{ $bal ? number_format($bal->allocated, 1) : '0.0' }}</p>
                            </div>
                        </div>
                    @empty
                        <x-empty message="No leave types available." icon="tag" />
                    @endforelse
                </div>
            </x-card>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    $(function () {
        $('#leave_type_id').on('change', function () {
            var val = $(this).val();
            var $note = $('#bal-note');
            if (!val) { $note.text('').removeClass('text-rose-600'); return; }
            $.getJSON('{{ route('me.leave.balance-lookup') }}', { leave_type_id: val })
                .done(function (data) {
                var avail = data.available != null ? data.available : 0;
                $note.removeClass('text-rose-600').text('Available: ' + avail + ' days');
                })
                .fail(function () {
                    $note.text('Could not load your balance. Please try again.').addClass('text-rose-600');
                });
        });
    });
</script>
@endpush
