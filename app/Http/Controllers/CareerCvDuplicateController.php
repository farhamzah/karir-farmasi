<?php

namespace App\Http\Controllers;

use App\Data\CareerActor;
use App\Models\CareerCv;
use App\Models\CareerProfile;
use App\Policies\CareerCvPolicy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CareerCvDuplicateController extends Controller
{
    public function store(Request $request, int $cv, CareerCvPolicy $policy): RedirectResponse
    {
        $actor = $request->attributes->get(CareerActor::class);
        $profile = CareerProfile::where('core_user_id', $actor->coreUserId)->firstOrFail();
        $source = $profile->cvs()->with(['sectionPreferences', 'itemPreferences'])->findOrFail($cv);
        abort_unless($policy->update($actor, $source), 404);
        $copy = DB::transaction(function () use ($source): CareerCv {
            $copy = $source->replicate(['name']);
            $copy->name = 'Salinan '.$source->name;
            $copy->save();
            foreach ($source->sectionPreferences as $preference) {
                $copy->sectionPreferences()->create($preference->only(['section_key', 'enabled', 'sort_order', 'display_title']));
            }
            foreach ($source->itemPreferences as $preference) {
                $copy->itemPreferences()->create($preference->only(['section_key', 'source_item_id', 'enabled', 'sort_order']));
            }

            return $copy;
        });

        return redirect()->route('cv.edit', $copy)->with('success', 'CV disalin. Silakan sesuaikan namanya.');
    }
}
