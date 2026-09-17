<?php

namespace App\Support;

use App\Data\CorePrincipal;
use App\Exceptions\CoreIdentityDenied;
use DateTimeImmutable;
use Throwable;

final class CorePrincipalNormalizer
{
    /** @param list<string> $allowedProgramIds */
    public function __construct(
        private readonly CareerRoleRegistry $roles,
        private readonly string $expectedAppCode,
        private readonly array $allowedProgramIds,
        private readonly string $environment,
    ) {}

    /** @param array<string, mixed> $payload */
    public function normalize(array $payload): CorePrincipal
    {
        $issuer = $this->requiredString($payload, 'issuer');
        $subject = $this->requiredString($payload, 'subject');
        $coreUserId = $this->requiredString($payload, 'core_user_id');
        $displayName = $this->requiredString($payload, 'display_name');
        $appCode = $this->requiredString($payload, 'app_code');

        if (parse_url($issuer, PHP_URL_SCHEME) !== 'https') {
            throw new CoreIdentityDenied('Identity issuer must use HTTPS.');
        }

        $email = $payload['email'] ?? null;
        if ($email !== null && (! is_string($email) || filter_var($email, FILTER_VALIDATE_EMAIL) === false)) {
            throw new CoreIdentityDenied('Identity email is malformed.');
        }

        if (($payload['active'] ?? null) !== true) {
            throw new CoreIdentityDenied('Identity is inactive.');
        }

        if (($payload['has_app_access'] ?? null) !== true) {
            throw new CoreIdentityDenied('Identity is not entitled to SAFA KARIR.');
        }

        if (! hash_equals($this->expectedAppCode, $appCode)) {
            throw new CoreIdentityDenied('Identity application code is invalid.');
        }

        $programIds = $this->programIds($payload['program_ids'] ?? null);
        $approvedAlumniGrant = ($payload['eligibility_source'] ?? null) === 'alumni_admin_approval'
            && in_array('farmasi', $this->careerScopes($payload['career_scope'] ?? null), true);
        $allowedCoreProgram = $this->allowedProgramIds !== []
            && array_intersect($programIds, $this->allowedProgramIds) !== [];

        if (! $approvedAlumniGrant && ! $allowedCoreProgram) {
            throw new CoreIdentityDenied('Identity is outside the allowed study-program scope.');
        }

        $synthetic = $payload['synthetic'] ?? null;
        if (! is_bool($synthetic)) {
            throw new CoreIdentityDenied('Identity synthetic marker is missing.');
        }

        if ($synthetic && ! in_array($this->environment, ['local', 'testing'], true)) {
            throw new CoreIdentityDenied('Synthetic identity is forbidden in this environment.');
        }

        try {
            $verifiedAt = new DateTimeImmutable($this->requiredString($payload, 'verified_at'));
        } catch (Throwable) {
            throw new CoreIdentityDenied('Identity verification timestamp is malformed.');
        }

        return new CorePrincipal(
            issuer: $issuer,
            subject: $subject,
            coreUserId: $coreUserId,
            displayName: $displayName,
            email: $email,
            active: true,
            appCode: $appCode,
            hasAppAccess: true,
            roles: $this->roles->activeRoles($payload['roles'] ?? null),
            programIds: $programIds,
            verifiedAt: $verifiedAt,
            synthetic: $synthetic,
        );
    }

    /** @param array<string, mixed> $payload */
    private function requiredString(array $payload, string $key): string
    {
        $value = $payload[$key] ?? null;

        if (! is_string($value) || trim($value) === '') {
            throw new CoreIdentityDenied("Identity field [$key] is missing or malformed.");
        }

        return $value;
    }

    /** @return list<string> */
    private function programIds(mixed $value): array
    {
        if (! is_array($value)) {
            throw new CoreIdentityDenied('Identity program scope is missing.');
        }

        $programIds = [];
        foreach ($value as $programId) {
            if ((! is_string($programId) && ! is_int($programId)) || trim((string) $programId) === '') {
                throw new CoreIdentityDenied('Identity program scope is malformed.');
            }
            $programIds[] = (string) $programId;
        }

        return array_values(array_unique($programIds));
    }

    /** @return list<string> */
    private function careerScopes(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter($value, fn ($scope): bool => is_string($scope) && trim($scope) !== ''));
    }
}
