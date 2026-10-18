<?php

namespace App\Enums;

enum LeaveStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function badge(): string
    {
        return match ($this) {
            self::Pending => 'bg-amber-100 text-amber-700',
            self::Approved => 'bg-emerald-100 text-emerald-700',
            self::Rejected => 'bg-rose-100 text-rose-700',
            self::Cancelled => 'bg-slate-100 text-slate-600',
        };
    }

    public static function badgeFor(?string $value): string
    {
        return self::tryFrom($value ?? '')?->badge() ?? 'bg-slate-100 text-slate-600';
    }
}
