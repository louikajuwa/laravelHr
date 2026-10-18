<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveBalance extends Model
{
    protected $fillable = [
        'user_id', 'leave_type_id', 'year', 'allocated', 'used', 'pending', 'carried_over',
    ];

    protected $casts = [
        'allocated' => 'decimal:1',
        'used' => 'decimal:1',
        'pending' => 'decimal:1',
        'carried_over' => 'decimal:1',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function getAvailableAttribute(): float
    {
        return (float) $this->allocated + (float) $this->carried_over
            - (float) $this->used - (float) $this->pending;
    }
}
