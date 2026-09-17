<?php

namespace App\Contracts;

use App\Data\CorePrincipal;

interface CoreIdentityGateway
{
    public function currentPrincipal(): CorePrincipal;

    public function authenticate(string $identifier, #[\SensitiveParameter] string $password): CorePrincipal;
}
