<?php

namespace App\Authorization;

use App\Data\CareerActor;
use App\Exceptions\CoreIdentityDenied;
use App\Support\CareerRoleRegistry;
use DateTimeImmutable;
use Illuminate\Http\Request;
use Throwable;

final class CurrentCareerActor
{
    public function __construct(
        private readonly CareerRoleRegistry $roles,
        private readonly CareerRoleCapabilities $capabilities,
    ) {}

    public function fromRequest(Request $request): CareerActor
    {
        $principal = $request->session()->get('core_principal');

        if (! is_array($principal)) {
            throw new CoreIdentityDenied('Authenticated SAFA KARIR principal is missing.');
        }

        $subject = $this->requiredString($principal, 'subject');
        $coreUserId = $this->requiredString($principal, 'core_user_id');
        $displayName = $this->requiredString($principal, 'display_name');
        $appCode = $this->requiredString($principal, 'app_code');

        if (($principal['active'] ?? null) !== true || ($principal['has_app_access'] ?? null) !== true) {
            throw new CoreIdentityDenied('Authenticated SAFA KARIR principal is inactive or not entitled.');
        }

        if (! hash_equals((string) config('core_identity.app_code'), $appCode)) {
            throw new CoreIdentityDenied('Authenticated principal has the wrong application scope.');
        }

        if (! is_array($principal['program_ids'] ?? null)) {
            throw new CoreIdentityDenied('Authenticated principal program scope is malformed.');
        }

        if (! is_bool($principal['synthetic'] ?? null)) {
            throw new CoreIdentityDenied('Authenticated principal synthetic marker is malformed.');
        }

        if ($principal['synthetic'] && ! app()->environment(['local', 'testing'])) {
            throw new CoreIdentityDenied('Synthetic principal is forbidden in this environment.');
        }

        try {
            new DateTimeImmutable($this->requiredString($principal, 'verified_at'));
        } catch (Throwable) {
            throw new CoreIdentityDenied('Authenticated principal verification time is malformed.');
        }

        $email = $principal['email'] ?? null;
        if ($email !== null && (! is_string($email) || filter_var($email, FILTER_VALIDATE_EMAIL) === false)) {
            throw new CoreIdentityDenied('Authenticated principal email is malformed.');
        }

        $roles = $this->roles->sessionRoles($principal['roles'] ?? null);

        return new CareerActor(
            subject: $subject,
            coreUserId: $coreUserId,
            displayName: $displayName,
            email: $email,
            roles: $roles,
            capabilities: $this->capabilities->forRoles($roles),
        );
    }

    public function optionalFromRequest(Request $request): ?CareerActor
    {
        try {
            return $this->fromRequest($request);
        } catch (CoreIdentityDenied) {
            return null;
        }
    }

    /** @param array<string, mixed> $principal */
    private function requiredString(array $principal, string $key): string
    {
        $value = $principal[$key] ?? null;

        if (! is_string($value) || trim($value) === '') {
            throw new CoreIdentityDenied("Authenticated principal field [$key] is missing or malformed.");
        }

        return $value;
    }
}
