<?php

namespace App\Support;

use Carbon\CarbonInterface;

/**
 * Exposes UTC-stored datetime attributes as epoch seconds so the frontend
 * can localize them to each viewer's timezone (never the server's).
 */
trait HasEpochTimes
{
    public function epoch(string $attribute): ?int
    {
        $value = $this->getAttribute($attribute);

        return $value instanceof CarbonInterface ? $value->getTimestamp() : null;
    }

    /**
     * @param  array<int,string>  $attributes
     * @return array<string,int|null>
     */
    public function epochMap(array $attributes): array
    {
        $out = [];
        foreach ($attributes as $attribute) {
            $out[$attribute.'_epoch'] = $this->epoch($attribute);
        }

        return $out;
    }
}
