<?php

namespace App\Support;

use App\Exceptions\CoreIdentityDenied;

final class CareerRoleRegistry
{
    public const Candidate = 'kandidat-karir';

    public const Administrator = 'admin-karir';

    public const Officer = 'petugas-karir';

    public const Viewer = 'viewer-karir';

    /** @var list<string> */
    private const ALLOWED = [
        self::Candidate,
        self::Administrator,
        self::Officer,
        self::Viewer,
    ];

    /** @return list<string> */
    public function activeRoles(mixed $roles): array
    {
        if (! is_array($roles) || $roles === []) {
            throw new CoreIdentityDenied('Identity role is missing.');
        }

        $normalized = [];

        foreach ($roles as $role) {
            if (! is_array($role) || ! is_string($role['slug'] ?? null) || ($role['active'] ?? null) !== true) {
                throw new CoreIdentityDenied('Identity role is inactive or malformed.');
            }

            if (! in_array($role['slug'], self::ALLOWED, true)) {
                throw new CoreIdentityDenied('Identity role is not recognized for SAFA KARIR.');
            }

            $normalized[] = $role['slug'];
        }

        return array_values(array_unique($normalized));
    }

    /** @return list<string> */
    public function sessionRoles(mixed $roles): array
    {
        if (! is_array($roles) || $roles === []) {
            throw new CoreIdentityDenied('Identity role is missing.');
        }

        $normalized = [];

        foreach ($roles as $role) {
            if (! is_string($role) || ! in_array($role, self::ALLOWED, true)) {
                throw new CoreIdentityDenied('Identity session role is not recognized for SAFA KARIR.');
            }

            $normalized[] = $role;
        }

        return array_values(array_unique($normalized));
    }
}
