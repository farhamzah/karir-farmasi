<?php

namespace App\Services;

use App\Contracts\CoreIdentityGateway;
use App\Data\CorePrincipal;
use App\Exceptions\CoreIdentityUnavailable;

final class UnavailableCoreIdentityGateway implements CoreIdentityGateway
{
    public function currentPrincipal(): CorePrincipal
    {
        throw new CoreIdentityUnavailable('Core identity is not configured for this environment.');
    }

    public function authenticate(string $identifier, #[\SensitiveParameter] string $password): CorePrincipal
    {
        throw new CoreIdentityUnavailable('Core identity is not configured for this environment.');
    }
}
