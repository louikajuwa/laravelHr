<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shift extends Model
{
    protected $fillable = [
        'name', 'start_time', 'end_time', 'grace_period_minutes',
        'break_minutes', 'working_days', 'overtime_threshold_minutes', 'is_active',
    ];

    protected $casts = [
        'working_days' => 'array',
        'is_active' => 'boolean',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function crossesMidnight(): bool
    {
        return $this->end_time <= $this->start_time;
    }

    public function expectedStart(Carbon $date): Carbon
    {
        return Carbon::parse($date->toDateString().' '.$this->start_time);
    }

    public function expectedEnd(Carbon $date): Carbon
    {
        $end = Carbon::parse($date->toDateString().' '.$this->end_time);

        return $this->crossesMidnight() ? $end->addDay() : $end;
    }

    public function worksOn(Carbon $date): bool
    {
        $days = $this->working_days ?: ['mon', 'tue', 'wed', 'thu', 'fri'];

        return in_array(strtolower($date->format('D')), array_map('strtolower', $days), true);
    }
}
