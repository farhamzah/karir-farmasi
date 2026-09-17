<?php

namespace App\Http\Controllers;

use App\Data\CareerActor;
use App\Policies\CareerProfileResourcePolicy;
use App\Profile\CareerProfileStore;
use App\Talent\TalentIndexBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CandidateDiscoverabilityController extends Controller
{
    public function update(Request $request, CareerProfileStore $profiles, CareerProfileResourcePolicy $policy, TalentIndexBuilder $indexBuilder): RedirectResponse
    {
        $data = $request->validate(['discoverable_by_verified_companies' => ['required', 'boolean'], 'discoverable_by_internal_leadership' => ['required', 'boolean']]);
        /** @var CareerActor $actor */
        $actor = $request->attributes->get(CareerActor::class);
        $profile = $profiles->forWrite($actor);
        abort_unless($policy->update($actor, $profile), 404);
        $profile->update($data + ['discoverability_updated_at' => now()]);
        $indexBuilder->rebuild($profile);

        return back()->with('success', 'Izin Direktori Talenta diperbarui.');
    }
}
