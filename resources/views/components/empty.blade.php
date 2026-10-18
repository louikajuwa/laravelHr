@props(['message' => 'Nothing to show yet.', 'icon' => 'grid'])

<div class="flex flex-col items-center justify-center gap-2 px-6 py-12 text-center">
    <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
        <x-icon :name="$icon" class="h-6 w-6" />
    </span>
    <p class="text-sm text-slate-500">{{ $message }}</p>
    @isset($action)<div class="mt-2">{{ $action }}</div>@endisset
</div>
