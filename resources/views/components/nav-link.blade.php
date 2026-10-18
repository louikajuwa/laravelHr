@props(['href' => '#', 'label' => '', 'icon' => 'dot', 'active' => false])

@php
    $paths = [
        'grid' => 'M4 5a1 1 0 011-1h5v6H4V5zm10-1h5a1 1 0 011 1v5h-6V4zM4 14h6v6H5a1 1 0 01-1-1v-5zm10 0h6v5a1 1 0 01-1 1h-5v-6z',
        'users' => 'M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1a4 4 0 100-8 4 4 0 000 8z',
        'building' => 'M3 21h18M5 21V5a1 1 0 011-1h7a1 1 0 011 1v16M9 8h2M9 12h2M19 21v-9a1 1 0 00-1-1h-3',
        'briefcase' => 'M3 9a2 2 0 012-2h14a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9zm6-2V5a2 2 0 012-2h2a2 2 0 012 2v2',
        'clock' => 'M12 8v4l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z',
        'chart' => 'M4 19V5m0 14h16M8 17v-5m4 5V9m4 8v-7',
        'calendar' => 'M8 7V3m8 4V3M4 11h16M5 5h14a1 1 0 011 1v13a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1z',
        'tag' => 'M7 7h.01M3 11l8.5 8.5a2 2 0 002.83 0l5.17-5.17a2 2 0 000-2.83L11 3H4a1 1 0 00-1 1v7z',
        'alert' => 'M12 9v4m0 4h.01M10.3 3.86l-8.1 14a1 1 0 00.86 1.5h16.2a1 1 0 00.86-1.5l-8.1-14a1 1 0 00-1.72 0z',
        'cash' => 'M3 7a1 1 0 011-1h16a1 1 0 011 1v10a1 1 0 01-1 1H4a1 1 0 01-1-1V7zm9 8a3 3 0 100-6 3 3 0 000 6z',
        'adjust' => 'M4 6h10M4 12h6M4 18h12M14 4v4M16 10v4M18 16v4',
        'shield' => 'M12 3l8 4v5c0 5-3.5 8-8 9-4.5-1-8-4-8-9V7l8-4z',
        'user' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM4 21v-1a6 6 0 0112 0v1',
        'dot' => 'M12 12h.01',
    ];
    $d = $paths[$icon] ?? $paths['dot'];
    $base = 'group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent-400';
    $on = 'bg-white/10 text-white shadow-inner';
    $off = 'text-slate-300/80 hover:bg-white/5 hover:text-white';
@endphp

<a href="{{ $href }}" @if ($active) aria-current="page" @endif class="{{ $base }} {{ $active ? $on : $off }}">
    <svg aria-hidden="true" class="h-5 w-5 shrink-0 {{ $active ? 'text-accent-400' : 'text-slate-400 group-hover:text-slate-200' }}"
         fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $d }}" />
    </svg>
    <span>{{ $label }}</span>
</a>
