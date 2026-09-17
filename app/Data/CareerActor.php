<?php

namespace App\Data;

use App\Authorization\CareerCapability;

final readonly class CareerActor
{
    /**
     * @param  list<string>  $roles
     * @param  list<CareerCapability>  $capabilities
     */
    public function __construct(
        public string $subject,
        public string $coreUserId,
        public string $displayName,
        public ?string $email,
        public array $roles,
        public array $capabilities,
    ) {}

    /** @return array{display_name: string, email: string|null, roles: list<string>, capabilities: list<string>} */
    public function toFrontendArray(bool $includeEmail = false): array
    {
        return [
            'display_name' => $this->displayName,
            'email' => $includeEmail ? $this->email : null,
            'roles' => $this->roles,
            'capabilities' => array_map(
                fn (CareerCapability $capability): string => $capability->value,
                $this->capabilities,
            ),
        ];
    }
}
