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

    /** @var array<string, array{label: string, eyebrow: string, description: string}> */
    private const PRESENTATION = [
        self::Candidate => [
            'label' => 'Alumni / Kandidat',
            'eyebrow' => 'RUANG KARIER PRIBADI',
            'description' => 'Kelola profil, CV, lowongan, event, lamaran, dan tracer Anda.',
        ],
        self::Administrator => [
            'label' => 'Administrator SAFA KARIR',
            'eyebrow' => 'KENDALI PLATFORM',
            'description' => 'Kelola pengguna, perusahaan, konten, lowongan, dan operasional platform.',
        ],
        self::Officer => [
            'label' => 'Petugas Karier',
            'eyebrow' => 'LAYANAN OPERASIONAL',
            'description' => 'Jalankan layanan alumni dan karier sesuai kewenangan yang diberikan.',
        ],
        self::Viewer => [
            'label' => 'Pimpinan / Viewer',
            'eyebrow' => 'RINGKASAN TERLINGKUP',
            'description' => 'Lihat ringkasan, direktori, dan laporan sesuai lingkup penugasan.',
        ],
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

    /**
     * @param  list<string>  $roles
     * @return list<array{slug: string, label: string, eyebrow: string, description: string}>
     */
    public function options(array $roles): array
    {
        return array_map(fn (string $role): array => [
            'slug' => $role,
            ...self::PRESENTATION[$role],
        ], $this->sessionRoles($roles));
    }
}
