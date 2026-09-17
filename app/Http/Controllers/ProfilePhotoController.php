<?php

namespace App\Http\Controllers;

use App\Data\CareerActor;
use App\Http\Requests\StoreProfilePhotoRequest;
use App\Policies\CareerProfileResourcePolicy;
use App\Profile\CareerProfileStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProfilePhotoController extends Controller
{
    public function store(StoreProfilePhotoRequest $request, CareerProfileStore $profiles, CareerProfileResourcePolicy $policy): RedirectResponse
    {
        $actor = $this->actor($request);
        $profile = $profiles->forWrite($actor);
        abort_unless($policy->update($actor, $profile), 404);

        $oldPath = $profile->photo_path;
        $path = $request->file('photo')->store("profiles/{$profile->getKey()}/photo", 'career_private');
        $profile->update(['photo_path' => $path]);

        if ($oldPath !== null) {
            Storage::disk('career_private')->delete($oldPath);
        }

        return back()->with('success', 'Foto profil disimpan secara privat.');
    }

    public function show(Request $request, CareerProfileStore $profiles, CareerProfileResourcePolicy $policy): StreamedResponse
    {
        $actor = $this->actor($request);
        $profile = $profiles->find($actor);
        abort_if($profile === null || ! $policy->view($actor, $profile) || $profile->photo_path === null, 404);
        abort_unless(Storage::disk('career_private')->exists($profile->photo_path), 404);

        return Storage::disk('career_private')->response($profile->photo_path);
    }

    public function destroy(Request $request, CareerProfileStore $profiles, CareerProfileResourcePolicy $policy): RedirectResponse
    {
        $actor = $this->actor($request);
        $profile = $profiles->find($actor);
        abort_if($profile === null || ! $policy->update($actor, $profile), 404);

        if ($profile->photo_path !== null) {
            Storage::disk('career_private')->delete($profile->photo_path);
            $profile->update(['photo_path' => null]);
        }

        return back()->with('success', 'Foto profil dihapus.');
    }

    private function actor(Request $request): CareerActor
    {
        /** @var CareerActor $actor */
        $actor = $request->attributes->get(CareerActor::class);

        return $actor;
    }
}
