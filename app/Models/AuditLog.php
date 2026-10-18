<?php

namespace App\Models;

use App\Support\HasEpochTimes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    use HasEpochTimes;

    public $timestamps = false;

    protected $fillable = [
        'actor_id', 'action', 'subject_type', 'subject_id',
        'old_values', 'new_values', 'description', 'ip_address', 'user_agent', 'created_at',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeByAction(Builder $q, ?string $action): Builder
    {
        return $action ? $q->where('action', $action) : $q;
    }

    public function scopeBetween(Builder $q, ?string $from, ?string $to): Builder
    {
        if ($from) {
            $q->where('created_at', '>=', $from.' 00:00:00');
        }
        if ($to) {
            $q->where('created_at', '<=', $to.' 23:59:59');
        }

        return $q;
    }
}
