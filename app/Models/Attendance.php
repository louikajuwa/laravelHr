<?php

namespace App\Models;

use App\Support\HasEpochTimes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attendance extends Model
{
    use HasEpochTimes;

    protected $fillable = [
        'user_id', 'date', 'time_in', 'time_out', 'shift_id', 'status',
        'is_late', 'late_minutes', 'early_leave_minutes', 'worked_minutes',
        'overtime_minutes', 'late_reason', 'is_manual', 'source', 'remarks',
    ];

    protected $casts = [
        'date' => 'date',
        'time_in' => 'datetime',
        'time_out' => 'datetime',
        'is_late' => 'boolean',
        'is_manual' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function corrections(): HasMany
    {
        return $this->hasMany(AttendanceCorrection::class);
    }

    public function scopeForUser(Builder $q, int $userId): Builder
    {
        return $q->where('user_id', $userId);
    }

    public function scopeBetween(Builder $q, string $from, string $to): Builder
    {
        return $q->whereBetween('date', [$from, $to]);
    }

    public function scopeLate(Builder $q): Builder
    {
        return $q->where('is_late', true);
    }
}
