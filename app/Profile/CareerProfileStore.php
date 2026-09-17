<?php

namespace App\Profile;

use App\Data\CareerActor;
use App\Models\CareerProfile;

final class CareerProfileStore
{
    public function find(CareerActor $actor): ?CareerProfile
    {
        return CareerProfile::query()->where('core_user_id', $actor->coreUserId)->first();
    }

    public function forWrite(CareerActor $actor): CareerProfile
    {
        return CareerProfile::query()->firstOrCreate(['core_user_id' => $actor->coreUserId]);
    }
}
