<?php

namespace App\Http\Controllers;

use App\Data\CareerActor;
use App\Models\OwnedProfileModel;
use App\Policies\CareerProfileResourcePolicy;
use App\Profile\CareerProfileStore;
use App\Profile\ProfileSection;
use App\Profile\ProfileSectionRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProfileFileController extends Controller
{
    public function show(
        Request $request,
        string $section,
        int $record,
        CareerProfileStore $profiles,
        CareerProfileResourcePolicy $policy,
        ProfileSectionRegistry $registry,
    ): StreamedResponse {
        $sectionType = ProfileSection::tryFrom($section);
        abort_if($sectionType === null || ! $sectionType->supportsAttachment(), 404);

        /** @var CareerActor $actor */
        $actor = $request->attributes->get(CareerActor::class);
        $profile = $profiles->find($actor);
        abort_if($profile === null, 404);

        $modelClass = $registry->modelClass($sectionType);
        /** @var OwnedProfileModel $item */
        $item = $modelClass::query()
            ->where('career_profile_id', $profile->getKey())
            ->findOrFail($record)
            ->setRelation('profile', $profile);
        abort_unless($policy->view($actor, $item), 404);

        $path = $item->getAttribute('attachment_path');
        abort_unless(is_string($path) && Storage::disk('career_private')->exists($path), 404);

        return Storage::disk('career_private')->download($path);
    }
}
