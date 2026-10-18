@extends('layouts.app')
@section('title', 'Report Late Arrival')

@section('content')
    <x-page-header title="Report Late Arrival" subtitle="Explain a late arrival — HR will review and update your record." />

    <x-card>
        <form method="POST" action="{{ route('me.late-arrivals.store') }}">
            @csrf
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <x-field label="Date" name="date" :required="true">
                    <x-input name="date" type="date" value="{{ old('date', now()->format('Y-m-d')) }}" required />
                </x-field>
            </div>

            <x-field label="Reason" name="reason" :required="true" class="mt-5">
                <x-textarea name="reason" required>{{ old('reason') }}</x-textarea>
            </x-field>

            <div class="mt-6 flex items-center justify-end gap-2">
                <x-button variant="secondary" :href="route('me.attendance.index')">Cancel</x-button>
                <x-button type="submit">Submit Report</x-button>
            </div>
        </form>
    </x-card>
@endsection
