<?php

namespace App\Http\Middleware;

use App\Authorization\AuthorizationAudit;
use App\Authorization\CareerAuthorization;
use App\Authorization\CareerCapability;
use App\Authorization\CurrentCareerActor;
use App\Data\CareerActor;
use App\Exceptions\CoreIdentityDenied;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use ValueError;

class RequireCareerCapability
{
    public function __construct(
        private readonly CurrentCareerActor $currentActor,
        private readonly CareerAuthorization $authorization,
        private readonly AuthorizationAudit $audit,
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $capabilityName): Response
    {
        try {
            $capability = CareerCapability::from($capabilityName);
        } catch (ValueError) {
            $this->audit->record($request, null, $capabilityName, false, 'unknown_capability');
            abort(403);
        }

        try {
            $actor = $this->currentActor->fromRequest($request);
        } catch (CoreIdentityDenied) {
            $this->audit->record($request, null, $capability->value, false, 'invalid_actor_context');
            abort(403);
        }

        if (! $this->authorization->allows($actor, $capability)) {
            $this->audit->record($request, $actor, $capability->value, false, 'capability_missing');
            abort(403);
        }

        if ($capability->isSensitive()) {
            $this->audit->record($request, $actor, $capability->value, true, 'capability_granted');
        }

        $request->attributes->set(CareerActor::class, $actor);

        return $next($request);
    }
}
