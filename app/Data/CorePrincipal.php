<?php

namespace App\Data;

use DateTimeImmutable;

final readonly class CorePrincipal
{
    /**
     * @param  list<string>  $roles
     * @param  list<string>  $programIds
     */
    public function __construct(
        public string $issuer,
        public string $subject,
        public string $coreUserId,
        public string $displayName,
        public ?string $email,
        public bool $active,
        public string $appCode,
        public bool $hasAppAccess,
        public array $roles,
        public array $programIds,
        public DateTimeImmutable $verifiedAt,
        public bool $synthetic,
    ) {}

    /**
     * @return array{issuer: string, subject: string, core_user_id: string, display_name: string, email: string|null, active: bool, app_code: string, has_app_access: bool, roles: list<string>, program_ids: list<string>, verified_at: string, synthetic: bool}
     */
    public function toSessionArray(): array
    {
        return [
            'issuer' => $this->issuer,
            'subject' => $this->subject,
            'core_user_id' => $this->coreUserId,
            'display_name' => $this->displayName,
            'email' => $this->email,
            'active' => $this->active,
            'app_code' => $this->appCode,
            'has_app_access' => $this->hasAppAccess,
            'roles' => $this->roles,
            'program_ids' => $this->programIds,
            'verified_at' => $this->verifiedAt->format(DATE_ATOM),
            'synthetic' => $this->synthetic,
        ];
    }
}
