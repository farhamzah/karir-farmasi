<?php

namespace App\Authorization;

use App\Contracts\OwnedCareerResource;
use App\Data\CareerActor;

abstract class OwnedResourcePolicy
{
    public function __construct(protected readonly CareerAuthorization $authorization) {}

    protected function ownsResource(CareerActor $actor, OwnedCareerResource $resource): bool
    {
        return $this->authorization->owns($actor, $resource);
    }

    protected function permitsOwned(
        CareerActor $actor,
        CareerCapability $capability,
        OwnedCareerResource $resource,
    ): bool {
        return $this->authorization->allowsOwned($actor, $capability, $resource);
    }
}
