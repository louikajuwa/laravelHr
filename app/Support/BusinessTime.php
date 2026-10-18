<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Company-local clock. Business rules (which calendar day an event belongs to,
 * shift start times) use HR_TIMEZONE; persisted datetimes stay in UTC.
 */
class BusinessTime
{
    public static function tz(): string
    {
        return (string) config('hr.timezone', 'UTC');
    }

    public static function now(): Carbon
    {
        return Carbon::now(self::tz());
    }

    public static function today(): Carbon
    {
        return Carbon::today(self::tz());
    }

    /** Express an instant in the company timezone. */
    public static function local(CarbonInterface $at): Carbon
    {
        return Carbon::instance($at)->setTimezone(self::tz());
    }

    /** Parse a wall-clock string (e.g. form input) as company-local time. */
    public static function parse(string $value): Carbon
    {
        return Carbon::parse($value, self::tz());
    }

    /** Convert to the storage timezone before assigning to a model attribute. */
    public static function toStorage(CarbonInterface $at): Carbon
    {
        return Carbon::instance($at)->setTimezone(config('app.timezone'));
    }
}
