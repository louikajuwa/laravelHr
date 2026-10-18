<?php

namespace App\Models;

use App\Models\Shift;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryStructure extends Model
{
    protected $fillable = [
        'user_id', 'basic_salary', 'pay_frequency', 'working_days_per_month',
        'overtime_rate_multiplier', 'late_deduction_per_minute', 'absence_deduction_mode',
        'perfect_attendance_bonus', 'allowances', 'effective_from', 'effective_to', 'is_active',
    ];

    protected $casts = [
        'basic_salary' => 'decimal:2',
        'overtime_rate_multiplier' => 'decimal:2',
        'late_deduction_per_minute' => 'decimal:2',
        'perfect_attendance_bonus' => 'decimal:2',
        'allowances' => 'array',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActiveOn(Builder $q, string $date): Builder
    {
        return $q->where('is_active', true)
            ->where('effective_from', '<=', $date)
            ->where(function (Builder $sub) use ($date) {
                $sub->whereNull('effective_to')->orWhere('effective_to', '>=', $date);
            })
            ->orderByDesc('effective_from');
    }

    public function dailyRate(): float
    {
        $days = max(1, (int) $this->working_days_per_month);

        return round((float) $this->basic_salary / $days, 2);
    }

    public function minuteRate(?Shift $shift = null): float
    {
        $minutesPerDay = 480; // 8h default
        if ($shift) {
            $start = strtotime($shift->start_time);
            $end = strtotime($shift->end_time);
            if ($end <= $start) {
                $end += 86400;
            }
            $minutesPerDay = max(60, ($end - $start) / 60 - (int) $shift->break_minutes);
        }

        return round($this->dailyRate() / $minutesPerDay, 4);
    }
}
