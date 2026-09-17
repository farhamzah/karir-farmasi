<?php

namespace App\Jobs;

use Illuminate\Support\Facades\DB;

final class JobAuditRecorder
{
    /** @param array<string, mixed> $metadata */
    public function record(string $event, string $actor, array $metadata): void
    {
        DB::table('audit_events')->insert([
            'event_type' => $event,
            'actor_reference' => $actor,
            'metadata' => json_encode($metadata, JSON_THROW_ON_ERROR),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
