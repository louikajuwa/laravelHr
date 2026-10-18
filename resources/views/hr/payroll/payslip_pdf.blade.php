<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 12px;
            color: #1e293b;
            margin: 0;
            padding: 0;
        }
        .sheet {
            width: 600px;
            margin: 0 auto;
            padding: 24px;
        }
        .header {
            background-color: #6366f1;
            color: #ffffff;
            padding: 16px 20px;
        }
        .header .company {
            font-size: 18px;
            font-weight: bold;
        }
        .header .doc {
            font-size: 13px;
            letter-spacing: 2px;
            margin-top: 2px;
        }
        table { width: 100%; border-collapse: collapse; }
        .meta-table td {
            padding: 8px 0;
            vertical-align: top;
            font-size: 12px;
        }
        .meta-table .label {
            color: #6366f1;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 10px;
            letter-spacing: 1px;
        }
        .section-title {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #6366f1;
            padding: 6px 8px;
            background-color: #eef2ff;
        }
        .lines td {
            padding: 6px 8px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 12px;
        }
        .lines td.amount { text-align: right; }
        .subtotal td {
            padding: 6px 8px;
            font-weight: bold;
            border-top: 2px solid #cbd5e1;
            font-size: 12px;
        }
        .subtotal td.amount { text-align: right; }
        .totals {
            margin-top: 16px;
            border-top: 2px solid #1e293b;
        }
        .totals td {
            padding: 8px;
            font-size: 13px;
        }
        .totals td.amount { text-align: right; font-weight: bold; }
        .totals .net td {
            font-size: 16px;
            font-weight: bold;
            color: #1e293b;
            border-top: 1px solid #cbd5e1;
        }
        .columns { width: 100%; }
        .columns td { vertical-align: top; width: 50%; }
        .columns td.left { padding-right: 10px; }
        .columns td.right { padding-left: 10px; }
        .footer {
            margin-top: 28px;
            padding-top: 12px;
            border-top: 1px solid #e2e8f0;
            font-size: 10px;
            color: #64748b;
            text-align: center;
        }
    </style>
</head>
<body>
    @php
        $earnings = $payroll->items->where('type', 'earning');
        $deductions = $payroll->items->where('type', 'deduction');
    @endphp
    <div class="sheet">
        <div class="header">
            <table>
                <tr>
                    <td><span class="company">{{ config('app.name') }}</span></td>
                    <td style="text-align: right;"><span class="doc">PAYSLIP</span></td>
                </tr>
            </table>
        </div>

        <table class="meta-table">
            <tr>
                <td style="width: 50%;">
                    <div class="label">Employee</div>
                    <div>{{ $payroll->user->name }}</div>
                    <div>{{ $payroll->user->employee_code }}</div>
                    <div>{{ $payroll->user->department?->name ?? '—' }}</div>
                </td>
                <td style="width: 50%; text-align: right;">
                    <div class="label">Pay Period</div>
                    <div>{{ $payroll->period_start->format('M d, Y') }} – {{ $payroll->period_end->format('M d, Y') }}</div>
                </td>
            </tr>
        </table>

        <table class="columns">
            <tr>
                <td class="left">
                    <table>
                        <tr><td colspan="2" class="section-title">Earnings</td></tr>
                        <tbody class="lines">
                            @forelse ($earnings as $item)
                                <tr>
                                    <td>{{ $item->label }}</td>
                                    <td class="amount">{{ number_format($item->amount, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="2">—</td></tr>
                            @endforelse
                        </tbody>
                        <tbody class="subtotal">
                            <tr>
                                <td>Gross</td>
                                <td class="amount">{{ number_format($payroll->gross, 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </td>
                <td class="right">
                    <table>
                        <tr><td colspan="2" class="section-title">Deductions</td></tr>
                        <tbody class="lines">
                            @forelse ($deductions as $item)
                                <tr>
                                    <td>{{ $item->label }}</td>
                                    <td class="amount">{{ number_format($item->amount, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="2">—</td></tr>
                            @endforelse
                        </tbody>
                        <tbody class="subtotal">
                            <tr>
                                <td>Total Deductions</td>
                                <td class="amount">{{ number_format($payroll->deductions_total, 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
        </table>

        <table class="totals">
            <tr>
                <td>Gross</td>
                <td class="amount">{{ number_format($payroll->gross, 2) }}</td>
            </tr>
            <tr>
                <td>Total Deductions</td>
                <td class="amount">{{ number_format($payroll->deductions_total, 2) }}</td>
            </tr>
            <tr class="net">
                <td>NET PAY</td>
                <td class="amount">{{ number_format($payroll->net_pay, 2) }}</td>
            </tr>
        </table>

        <div class="footer">
            Generated by {{ $payroll->generatedBy?->name }} on {{ $payroll->generated_at?->format('M d, Y') }}
        </div>
    </div>
</body>
</html>
