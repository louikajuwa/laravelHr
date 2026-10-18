@extends('layouts.app')
@section('title', 'Leave Balances')

@section('content')
    <x-page-header title="Leave Balances" subtitle="Your entitlements for {{ now()->year }}." />

    @if ($balances->isEmpty())
        <x-empty message="No leave balances allocated yet." icon="tag" />
    @else
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($balances as $b)
                <x-card :title="$b->leaveType->name">
                    <p class="text-3xl font-bold text-slate-900">{{ number_format($b->available, 1) }}</p>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Days available</p>

                    <dl class="mt-4 space-y-2 border-t border-slate-100 pt-4 text-sm">
                        <div class="flex items-center justify-between">
                            <dt class="text-slate-500">Allocated</dt>
                            <dd class="font-medium text-slate-800">{{ number_format($b->allocated, 1) }}</dd>
                        </div>
                        <div class="flex items-center justify-between">
                            <dt class="text-slate-500">Used</dt>
                            <dd class="font-medium text-slate-800">{{ number_format($b->used, 1) }}</dd>
                        </div>
                        <div class="flex items-center justify-between">
                            <dt class="text-slate-500">Pending</dt>
                            <dd class="font-medium text-slate-800">{{ number_format($b->pending, 1) }}</dd>
                        </div>
                    </dl>
                </x-card>
            @endforeach
        </div>
    @endif
@endsection
