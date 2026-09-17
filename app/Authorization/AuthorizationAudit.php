<?php

namespace App\Authorization;

use App\Data\CareerActor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class AuthorizationAudit
{
    public function record(
        Request $request,
        ?CareerActor $actor,
        string $capability,
        bool $allowed,
        string $reason,
    ): void {
        DB::table('audit_events')->insert([
            'event_type' => $allowed ? 'authorization.allowed' : 'authorization.denied',
            'actor_reference' => $actor?->coreUserId,
            'metadata' => json_encode([
                'capability' => $capability,
                'decision' => $allowed ? 'allow' : 'deny',
                'reason' => $reason,
                'route_name' => $request->route()?->getName(),
                'http_method' => $request->method(),
            ], JSON_THROW_ON_ERROR),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
