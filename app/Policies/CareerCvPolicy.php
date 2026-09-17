<?php

namespace App\Policies;

use App\Authorization\CareerCapability;
use App\Authorization\OwnedResourcePolicy;
use App\Contracts\OwnedCareerResource;
use App\Data\CareerActor;

final class CareerCvPolicy extends OwnedResourcePolicy
{
    public function view(CareerActor $actor, OwnedCareerResource $resource): bool
    {
        return $this->permitsOwned($actor, CareerCapability::CvViewOwn, $resource);
    }

    public function update(CareerActor $actor, OwnedCareerResource $resource): bool
    {
        return $this->permitsOwned($actor, CareerCapability::CvManageOwn, $resource);
    }

    public function delete(CareerActor $actor, OwnedCareerResource $resource): bool
    {
        return $this->update($actor, $resource);
    }
}
