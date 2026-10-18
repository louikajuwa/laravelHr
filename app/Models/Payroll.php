<?php

namespace App\Models;

use App\Support\HasEpochTimes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payroll extends Model
{
    use HasEpochTimes, HasFactory;

    protected $fillable = [
        'user_id', 'period_start', 'period_end', 'label', 'basic', 'overtime_pay',
        'bonuses', 'allowances_total', 'gross', 'late_deduction', 'absence_deduction',
        'unpaid_leave_deduction', 'other_deductions', 'deductions_total', 'net_pay',
        'status', 'generated_by', 'generated_at', 'finalized_at', 'paid_at', 'meta',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'basic' => 'decimal:2',
        'overtime_pay' => 'decimal:2',
        'bonuses' => 'decimal:2',
        'allowances_total' => 'decimal:2',
        'gross' => 'decimal:2',
        'late_deduction' => 'decimal:2',
        'absence_deduction' => 'decimal:2',
        'unpaid_leave_deduction' => 'decimal:2',
        'other_deductions' => 'decimal:2',
        'deductions_total' => 'decimal:2',
        'net_pay' => 'decimal:2',
        'generated_at' => 'datetime',
        'finalized_at' => 'datetime',
        'paid_at' => 'datetime',
        'meta' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PayrollItem::class);
    }

    public function scopeForPeriod(Builder $q, string $start, string $end): Builder
    {
        return $q->where('period_start', $start)->where('period_end', $end);
    }
}
