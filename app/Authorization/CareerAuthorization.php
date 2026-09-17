<?php

namespace App\Authorization;

use App\Contracts\OwnedCareerResource;
use App\Data\CareerActor;

final class CareerAuthorization
{
    public function allows(CareerActor $actor, CareerCapability $capability): bool
    {
        return in_array($capability, $actor->capabilities, true);
    }

    public function owns(CareerActor $actor, OwnedCareerResource $resource): bool
    {
        return hash_equals($actor->coreUserId, $resource->ownerCoreUserId());
    }

    public function allowsOwned(
        CareerActor $actor,
        CareerCapability $capability,
        OwnedCareerResource $resource,
    ): bool {
        return $this->allows($actor, $capability) && $this->owns($actor, $resource);
    }
}
