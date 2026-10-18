<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditLogger
{
    /**
     * Record a curated domain event.
     *
     * @param  array<string,mixed>  $old
     * @param  array<string,mixed>  $new
     */
    public function log(
        string $action,
        ?Model $subject = null,
        array $old = [],
        array $new = [],
        ?string $description = null,
        ?int $actorId = null,
    ): AuditLog {
        $request = request();

        return AuditLog::create([
            'actor_id' => $actorId ?? Auth::id(),
            'action' => $action,
            'subject_type' => $subject ? $subject->getMorphClass() : null,
            'subject_id' => $subject?->getKey(),
            'old_values' => $old ?: null,
            'new_values' => $new ?: null,
            'description' => $description,
            'ip_address' => $request?->ip(),
            'user_agent' => $request ? substr((string) $request->userAgent(), 0, 1000) : null,
            'created_at' => now(),
        ]);
    }
}
