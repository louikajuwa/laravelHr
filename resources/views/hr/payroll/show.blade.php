@extends('layouts.app')
@section('title', 'Payslip · ' . $payroll->user->name)

@section('content')
    <x-page-header :title="'Payslip · ' . $payroll->user->name"
                   :subtitle="$payroll->period_start->format('M d, Y') . ' – ' . $payroll->period_end->format('M d, Y')">
        <x-slot:actions>
            @if ($payroll->status === 'draft')
                <form method="POST" action="{{ route('hr.payroll.finalize', $payroll) }}" data-confirm="Finalize this payslip?" data-confirm-text="Finalize" data-confirm-class="bg-emerald-600 hover:bg-emerald-700">
                    @csrf
                    <x-button variant="success" type="submit"><x-icon name="check" class="h-4 w-4" /> Finalize</x-button>
                </form>
            @elseif ($payroll->status === 'finalized')
                <form method="POST" action="{{ route('hr.payroll.pay', $payroll) }}" data-confirm="Mark this payslip as paid?" data-confirm-text="Mark paid" data-confirm-class="bg-accent-600 hover:bg-accent-700">
                    @csrf
                    <x-button type="submit"><x-icon name="cash" class="h-4 w-4" /> Mark Paid</x-button>
                </form>
            @endif
            <x-button variant="secondary" :href="route('hr.payroll.payslip', $payroll)"><x-icon name="download" class="h-4 w-4" /> Download PDF</x-button>
        </x-slot:actions>
    </x-page-header>

    @php
        $earnings = $payroll->items->where('type', 'earning');
        $deductions = $payroll->items->where('type', 'deduction');
        $meta = $payroll->meta ?? [];
    @endphp

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-card title="Earnings">
                @forelse ($earnings as $item)
                    <div class="flex items-center justify-between gap-4 py-1.5 text-sm">
                        <span class="text-slate-600">{{ $item->label }}</span>
                        <span class="font-medium text-slate-800">{{ number_format($item->amount, 2) }}</span>
                    </div>
                @empty
                    <p class="py-1.5 text-sm text-slate-500">No earnings recorded.</p>
                @endforelse
            </x-card>

            <x-card title="Deductions">
                @forelse ($deductions as $item)
                    <div class="flex items-center justify-between gap-4 py-1.5 text-sm">
                        <span class="text-slate-600">{{ $item->label }}</span>
                        <span class="font-medium text-rose-600">{{ number_format($item->amount, 2) }}</span>
                    </div>
                @empty
                    <p class="py-1.5 text-sm text-slate-500">No deductions recorded.</p>
                @endforelse
            </x-card>

            <x-card title="Period Snapshot">
                @if (!empty($meta))
                    @php
                        $snapshot = [
                            'Days Present' => $meta['days_present'] ?? null,
                            'Days Absent' => $meta['days_absent'] ?? null,
                            'Late Minutes' => $meta['late_minutes'] ?? null,
                            'Overtime Minutes' => $meta['overtime_minutes'] ?? null,
                            'Paid Leave Days' => $meta['paid_leave_days'] ?? null,
                            'Unpaid Leave Days' => $meta['unpaid_leave_days'] ?? null,
                        ];
                    @endphp
                    <div class="grid grid-cols-2 gap-4 text-sm sm:grid-cols-3">
                        @foreach ($snapshot as $label => $value)
                            <div>
                                <p class="text-slate-500">{{ $label }}</p>
                                <p class="font-semibold text-slate-800">{{ $value ?? '—' }}</p>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-slate-500">No snapshot data available.</p>
                @endif
            </x-card>
        </div>

        <div class="space-y-6 lg:col-span-1">
            <x-card title="Summary">
                <dl class="space-y-3 text-sm">
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-slate-500">Gross</dt>
                        <dd class="font-medium text-slate-700">{{ number_format($payroll->gross, 2) }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-slate-500">Total Deductions</dt>
                        <dd class="font-medium text-rose-600">{{ number_format($payroll->deductions_total, 2) }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4 border-t border-slate-100 pt-3">
                        <dt class="text-slate-500">Net Pay</dt>
                        <dd class="text-2xl font-bold text-slate-900">{{ number_format($payroll->net_pay, 2) }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4 border-t border-slate-100 pt-3">
                        <dt class="text-slate-500">Status</dt>
                        <dd><x-badge :color="\App\Enums\PayrollStatus::badgeFor($payroll->status)">{{ ucfirst($payroll->status) }}</x-badge></dd>
                    </div>
                </dl>
            </x-card>

            <x-card title="Employee">
                <dl class="space-y-2.5 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">Name</dt>
                        <dd class="text-right font-medium text-slate-700">{{ $payroll->user->name }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">Code</dt>
                        <dd class="text-right font-medium text-slate-700">{{ $payroll->user->employee_code }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">Department</dt>
                        <dd class="text-right font-medium text-slate-700">{{ $payroll->user->department?->name ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">Generated</dt>
                        <dd class="text-right font-medium text-slate-700">
                            <span data-epoch="{{ $payroll->epoch('generated_at') }}" data-format="MMM D, YYYY · h:mm A">—</span>
                        </dd>
                    </div>
                </dl>
            </x-card>
        </div>
    </div>
@endsection
