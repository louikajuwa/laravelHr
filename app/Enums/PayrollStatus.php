<?php

namespace App\Enums;

enum PayrollStatus: string
{
    case Draft = 'draft';
    case Finalized = 'finalized';
    case Paid = 'paid';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function badge(): string
    {
        return match ($this) {
            self::Draft => 'bg-slate-100 text-slate-600',
            self::Finalized => 'bg-sky-100 text-sky-700',
            self::Paid => 'bg-emerald-100 text-emerald-700',
        };
    }

    public static function badgeFor(?string $value): string
    {
        return self::tryFrom($value ?? '')?->badge() ?? 'bg-slate-100 text-slate-600';
    }
}
