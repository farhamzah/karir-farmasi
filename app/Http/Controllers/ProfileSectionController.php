<?php

namespace App\Http\Controllers;

use App\Data\CareerActor;
use App\Http\Requests\UpsertProfileSectionRequest;
use App\Models\CareerProfile;
use App\Models\OwnedProfileModel;
use App\Policies\CareerProfileResourcePolicy;
use App\Profile\CareerProfileStore;
use App\Profile\ProfileSection;
use App\Profile\ProfileSectionRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ProfileSectionController extends Controller
{
    public function index(
        Request $request,
        string $section,
        CareerProfileStore $profiles,
        CareerProfileResourcePolicy $policy,
        ProfileSectionRegistry $registry,
    ): Response {
        $sectionType = $this->section($section);
        $actor = $this->actor($request);
        $profile = $profiles->find($actor);

        if ($profile !== null) {
            abort_unless($policy->view($actor, $profile), 404);
        }

        if ($profile === null) {
            $records = collect();
        } else {
            $recordsQuery = $profile->{$sectionType->relation()}();
            $records = $sectionType->singular()
                ? $recordsQuery->get()
                : $recordsQuery->orderBy('sort_order')->orderBy('id')->get();
        }

        return Inertia::render('Profile/Section', [
            'section' => [
                'key' => $sectionType->value,
                'label' => $sectionType->label(),
                'singular' => $sectionType->singular(),
                ...$registry->uiDefinition($sectionType),
            ],
            'records' => $records->map(fn ($record): array => $registry->serialize($sectionType, $record))->values(),
        ]);
    }

    public function store(
        UpsertProfileSectionRequest $request,
        string $section,
        CareerProfileStore $profiles,
        CareerProfileResourcePolicy $policy,
        ProfileSectionRegistry $registry,
    ): RedirectResponse {
        $sectionType = $this->section($section);
        $actor = $this->actor($request);
        $profile = $profiles->forWrite($actor);
        abort_unless($policy->update($actor, $profile), 404);
        $data = $this->data($request, $sectionType, $profile, $registry);

        if ($sectionType->singular()) {
            $record = $profile->{$sectionType->relation()}()->firstOrNew();
            abort_unless($policy->update($actor, $record->setRelation('profile', $profile)), 404);
            $record->fill($data)->save();
        } else {
            $profile->{$sectionType->relation()}()->create($data);
        }

        return back()->with('success', "{$sectionType->label()} disimpan.");
    }

    public function update(
        UpsertProfileSectionRequest $request,
        string $section,
        int $record,
        CareerProfileStore $profiles,
        CareerProfileResourcePolicy $policy,
        ProfileSectionRegistry $registry,
    ): RedirectResponse {
        $sectionType = $this->section($section);
        $actor = $this->actor($request);
        $profile = $this->profile($actor, $profiles);
        $item = $this->record($profile, $sectionType, $record, $registry);
        abort_unless($policy->update($actor, $item), 404);
        $this->guardVerifiedEducation($sectionType, $item);

        $oldAttachment = $item->getAttribute('attachment_path');
        $item->update($this->data($request, $sectionType, $profile, $registry));
        $this->deleteReplacedAttachment($request, $sectionType, $oldAttachment);

        return back()->with('success', "{$sectionType->label()} diperbarui.");
    }

    public function destroy(
        Request $request,
        string $section,
        int $record,
        CareerProfileStore $profiles,
        CareerProfileResourcePolicy $policy,
        ProfileSectionRegistry $registry,
    ): RedirectResponse {
        $sectionType = $this->section($section);
        $actor = $this->actor($request);
        $profile = $this->profile($actor, $profiles);
        $item = $this->record($profile, $sectionType, $record, $registry);
        abort_unless($policy->delete($actor, $item), 404);
        $this->guardVerifiedEducation($sectionType, $item);
        $attachment = $item->getAttribute('attachment_path');
        $item->delete();

        if (is_string($attachment)) {
            Storage::disk('career_private')->delete($attachment);
        }

        return back()->with('success', "{$sectionType->label()} dihapus.");
    }

    private function section(string $section): ProfileSection
    {
        $sectionType = ProfileSection::tryFrom($section);
        abort_if($sectionType === null, 404);

        return $sectionType;
    }

    private function actor(Request $request): CareerActor
    {
        /** @var CareerActor $actor */
        $actor = $request->attributes->get(CareerActor::class);

        return $actor;
    }

    private function profile(CareerActor $actor, CareerProfileStore $profiles): CareerProfile
    {
        $profile = $profiles->find($actor);
        abort_if($profile === null, 404);

        return $profile;
    }

    private function record(
        CareerProfile $profile,
        ProfileSection $section,
        int $recordId,
        ProfileSectionRegistry $registry,
    ): OwnedProfileModel {
        $modelClass = $registry->modelClass($section);
        $record = $modelClass::query()
            ->where('career_profile_id', $profile->getKey())
            ->findOrFail($recordId);

        return $record->setRelation('profile', $profile);
    }

    /** @return array<string, mixed> */
    private function data(
        UpsertProfileSectionRequest $request,
        ProfileSection $section,
        CareerProfile $profile,
        ProfileSectionRegistry $registry,
    ): array {
        $data = $registry->normalize($section, $request->validated());

        if ($section === ProfileSection::Experience && ($data['currently_active'] ?? false)) {
            $data['end_date'] = null;
        }

        if ($section->supportsAttachment() && $request->hasFile('attachment')) {
            $data['attachment_path'] = $request->file('attachment')->store(
                "profiles/{$profile->getKey()}/{$section->value}",
                'career_private',
            );
        }

        return $data;
    }

    private function guardVerifiedEducation(ProfileSection $section, OwnedProfileModel $record): void
    {
        if ($section === ProfileSection::Education && $record->getAttribute('source') === 'core_verified') {
            abort(403, 'Pendidikan terverifikasi Core tidak dapat diubah dari SAFA KARIR.');
        }
    }

    private function deleteReplacedAttachment(
        UpsertProfileSectionRequest $request,
        ProfileSection $section,
        mixed $oldAttachment,
    ): void {
        if ($section->supportsAttachment() && $request->hasFile('attachment') && is_string($oldAttachment)) {
            Storage::disk('career_private')->delete($oldAttachment);
        }
    }
}
