@props(['label' => '', 'value' => '', 'sub' => null, 'icon' => 'grid', 'tone' => 'accent'])

@php
    $tones = [
        'accent' => 'bg-accent-50 text-accent-600',
        'emerald' => 'bg-emerald-50 text-emerald-600',
        'amber' => 'bg-amber-50 text-amber-600',
        'rose' => 'bg-rose-50 text-rose-600',
        'sky' => 'bg-sky-50 text-sky-600',
        'violet' => 'bg-violet-50 text-violet-600',
    ];
    $toneClass = $tones[$tone] ?? $tones['accent'];
@endphp

<div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <div class="flex items-start justify-between">
        <div>
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ $label }}</p>
            <p class="mt-2 text-2xl font-bold text-slate-900">{{ $value }}</p>
            @if ($sub)<p class="mt-1 text-xs text-slate-500">{{ $sub }}</p>@endif
        </div>
        <span class="flex h-10 w-10 items-center justify-center rounded-xl {{ $toneClass }}">
            <x-icon :name="$icon" class="h-5 w-5" />
        </span>
    </div>
</div>
