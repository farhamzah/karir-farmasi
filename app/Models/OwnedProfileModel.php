<?php

namespace App\Models;

use App\Contracts\OwnedCareerResource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

abstract class OwnedProfileModel extends Model implements OwnedCareerResource
{
    public function profile(): BelongsTo
    {
        return $this->belongsTo(CareerProfile::class, 'career_profile_id');
    }

    public function ownerCoreUserId(): string
    {
        return (string) $this->profile->core_user_id;
    }
}
