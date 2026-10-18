<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveType extends Model
{
    protected $fillable = [
        'name', 'code', 'default_days_per_year', 'is_paid',
        'requires_approval', 'carry_over', 'max_carry_over_days', 'is_active',
    ];

    protected $casts = [
        'default_days_per_year' => 'decimal:1',
        'max_carry_over_days' => 'decimal:1',
        'is_paid' => 'boolean',
        'requires_approval' => 'boolean',
        'carry_over' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function requests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function balances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }
}
