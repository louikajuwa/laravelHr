@props(['variant' => 'primary', 'href' => null, 'type' => 'button'])

@php
    $variants = [
        'primary' => 'bg-accent-600 text-white hover:bg-accent-700 shadow-sm',
        'secondary' => 'bg-white text-slate-700 border border-slate-300 hover:bg-slate-50',
        'danger' => 'bg-rose-600 text-white hover:bg-rose-700',
        'success' => 'bg-emerald-600 text-white hover:bg-emerald-700',
        'ghost' => 'text-slate-600 hover:bg-slate-100',
    ];
    $cls = 'inline-flex items-center justify-center gap-1.5 rounded-xl px-3.5 py-2 text-sm font-semibold transition disabled:opacity-50 '.($variants[$variant] ?? $variants['primary']);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $cls]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $cls]) }}>{{ $slot }}</button>
@endif
