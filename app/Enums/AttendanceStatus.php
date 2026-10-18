<?php

namespace App\Enums;

enum AttendanceStatus: string
{
    case Present = 'present';
    case Late = 'late';
    case Absent = 'absent';
    case OnLeave = 'on_leave';
    case HalfDay = 'half_day';
    case Holiday = 'holiday';

    public function label(): string
    {
        return match ($this) {
            self::Present => 'Present',
            self::Late => 'Late',
            self::Absent => 'Absent',
            self::OnLeave => 'On Leave',
            self::HalfDay => 'Half Day',
            self::Holiday => 'Holiday',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Present => 'bg-emerald-100 text-emerald-700',
            self::Late => 'bg-amber-100 text-amber-700',
            self::Absent => 'bg-rose-100 text-rose-700',
            self::OnLeave => 'bg-sky-100 text-sky-700',
            self::HalfDay => 'bg-violet-100 text-violet-700',
            self::Holiday => 'bg-slate-100 text-slate-600',
        };
    }

    public static function badgeFor(?string $value): string
    {
        return self::tryFrom($value ?? '')?->badge() ?? 'bg-slate-100 text-slate-600';
    }

    public static function labelFor(?string $value): string
    {
        return self::tryFrom($value ?? '')?->label() ?? ucfirst((string) $value);
    }
}
