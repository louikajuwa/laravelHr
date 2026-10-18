@if (session('status'))
    <div data-flash data-flash-auto role="status" class="mb-4 flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
        <x-icon name="check" class="mt-0.5 h-4 w-4 shrink-0" />
        <span class="flex-1">{{ session('status') }}</span>
        <button type="button" data-flash-close aria-label="Dismiss" class="rounded text-emerald-600 hover:text-emerald-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500"><span aria-hidden="true">&times;</span></button>
    </div>
@endif

@if ($errors->any())
    {{-- Errors are not auto-dismissed. Messages are listed so errors without a visible field are never hidden. --}}
    <div data-flash role="alert" class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
        <div class="flex items-start gap-3">
            <x-icon name="alert" class="mt-0.5 h-4 w-4 shrink-0" />
            <div class="flex-1">
                <p class="font-semibold">Please fix the errors below.</p>
                <ul class="mt-1 list-inside list-disc space-y-0.5 text-xs text-rose-700">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            <button type="button" data-flash-close aria-label="Dismiss" class="rounded text-rose-600 hover:text-rose-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rose-500"><span aria-hidden="true">&times;</span></button>
        </div>
    </div>
@endif
