<?php

namespace App\Observers;

use App\Models\CareerEventRegistration;
use App\Models\CareerProfile;
use App\Models\OwnedProfileModel;
use App\Talent\TalentIndexBuilder;
use Illuminate\Database\Eloquent\Model;

final class TalentIndexObserver
{
    public function saved(Model $model): void
    {
        $this->rebuild($model);
    }

    public function deleted(Model $model): void
    {
        $this->rebuild($model);
    }

    private function rebuild(Model $model): void
    {
        $profile = match (true) {
            $model instanceof CareerProfile => $model->exists ? $model : null,
            $model instanceof OwnedProfileModel => CareerProfile::query()->find($model->career_profile_id),
            $model instanceof CareerEventRegistration => CareerProfile::query()->find($model->career_profile_id),
            default => null,
        };

        if ($profile !== null) {
            app(TalentIndexBuilder::class)->rebuild($profile);
        }
    }
}
