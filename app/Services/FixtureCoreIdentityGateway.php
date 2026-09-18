<?php

namespace App\Services;

use App\Contracts\CoreIdentityGateway;
use App\Data\CorePrincipal;
use DateTimeImmutable;
use LogicException;

final class FixtureCoreIdentityGateway implements CoreIdentityGateway
{
    public function __construct(private readonly string $environment)
    {
        if (! in_array($environment, ['local', 'testing'], true)) {
            throw new LogicException("Synthetic identity is forbidden in [$environment].");
        }
    }

    public function currentPrincipal(): CorePrincipal
    {
        return new CorePrincipal(
            issuer: 'https://fixture.invalid',
            subject: 'fixture:alumni-001',
            coreUserId: 'fixture-core-user-001',
            displayName: 'Alumni Farmasi Sintetis',
            email: 'alumni@fixture.invalid',
            active: true,
            appCode: 'karir-farmasi',
            hasAppAccess: true,
            roles: ['kandidat-karir'],
            programIds: ['farmasi-ubp'],
            verifiedAt: new DateTimeImmutable('2026-09-11T00:00:00+07:00'),
            synthetic: true,
        );
    }

    public function authenticate(string $identifier, #[\SensitiveParameter] string $password): CorePrincipal
    {
        $normalizedIdentifier = mb_strtolower(trim($identifier));

        $staffFixtures = [
            'admin@fixture.invalid' => ['admin-001', 'Admin Karir Sintetis', 'admin-karir'],
            'multi.role@fixture.invalid' => ['multi-role-001', 'Pengguna Multi Peran Sintetis', ['kandidat-karir', 'admin-karir']],
            'petugas@fixture.invalid' => ['petugas-001', 'Petugas Karir Sintetis', 'petugas-karir'],
            'viewer@fixture.invalid' => ['viewer-001', 'Viewer Karir Sintetis', 'viewer-karir'],
            'dekan@fixture.invalid' => ['dekan-001', 'Dekan Farmasi Sintetis', 'viewer-karir'],
            'kaprodi@fixture.invalid' => ['kaprodi-001', 'Kaprodi Farmasi Sintetis', 'viewer-karir'],
        ];

        $candidateFixtures = [
            'alya.maharani@fixture.invalid' => ['user-complete-001', 'Alya Nur Maharani, S.Farm.'],
        ];

        if (isset($candidateFixtures[$normalizedIdentifier])) {
            [$id, $displayName] = $candidateFixtures[$normalizedIdentifier];

            return new CorePrincipal(
                issuer: 'https://fixture.invalid',
                subject: 'fixture:'.$id,
                coreUserId: 'fixture-core-'.$id,
                displayName: $displayName,
                email: $normalizedIdentifier,
                active: true,
                appCode: 'karir-farmasi',
                hasAppAccess: true,
                roles: ['kandidat-karir'],
                programIds: ['farmasi-ubp'],
                verifiedAt: new DateTimeImmutable('2026-09-11T00:00:00+07:00'),
                synthetic: true,
            );
        }

        if (isset($staffFixtures[$normalizedIdentifier])) {
            [$id, $displayName, $role] = $staffFixtures[$normalizedIdentifier];
            $fixtureRoles = is_array($role) ? $role : [$role];

            return new CorePrincipal(
                issuer: 'https://fixture.invalid',
                subject: 'fixture:'.$id,
                coreUserId: 'fixture-core-'.$id,
                displayName: $displayName,
                email: $normalizedIdentifier,
                active: true,
                appCode: 'karir-farmasi',
                hasAppAccess: true,
                roles: $fixtureRoles,
                programIds: ['farmasi-ubp'],
                verifiedAt: new DateTimeImmutable('2026-09-11T00:00:00+07:00'),
                synthetic: true,
            );
        }

        return $this->currentPrincipal();
    }
}
