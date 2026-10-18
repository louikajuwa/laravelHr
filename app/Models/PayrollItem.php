<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollItem extends Model
{
    protected $fillable = ['payroll_id', 'type', 'code', 'label', 'amount', 'meta'];

    protected $casts = [
        'amount' => 'decimal:2',
        'meta' => 'array',
    ];

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }
}
