<?php

namespace App\Http\Controllers;

use App\Data\CareerActor;
use App\Http\Requests\UpdateCareerProfileRequest;
use App\Models\CareerProfile;
use App\Policies\CareerProfileResourcePolicy;
use App\Profile\CareerProfileStore;
use App\Profile\ProfileSection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function index(Request $request, CareerProfileStore $profiles, CareerProfileResourcePolicy $policy): Response
    {
        $actor = $this->actor($request);
        $profile = $profiles->find($actor);
        $this->authorizeExisting($actor, $profile, $policy);

        return Inertia::render('Profile/Overview', [
            'profile' => $this->profileData($profile),
            'progress' => $this->progress($profile),
            'sections' => $this->sectionSummaries($profile),
        ]);
    }

    public function edit(Request $request, CareerProfileStore $profiles, CareerProfileResourcePolicy $policy): Response
    {
        $actor = $this->actor($request);
        $profile = $profiles->find($actor);
        $this->authorizeExisting($actor, $profile, $policy);

        return Inertia::render('Profile/Edit', ['profile' => $this->profileData($profile)]);
    }

    public function update(UpdateCareerProfileRequest $request, CareerProfileStore $profiles, CareerProfileResourcePolicy $policy): RedirectResponse
    {
        $actor = $this->actor($request);
        $profile = $profiles->forWrite($actor);
        abort_unless($policy->update($actor, $profile), 404);
        $profile->update($request->validated());

        return redirect()->route('profile.index')->with('success', 'Profil profesional disimpan.');
    }

    public function confirm(Request $request, CareerProfileStore $profiles, CareerProfileResourcePolicy $policy): RedirectResponse
    {
        $actor = $this->actor($request);
        $profile = $profiles->forWrite($actor);
        abort_unless($policy->update($actor, $profile), 404);
        $profile->update(['last_confirmed_at' => now()]);

        return back()->with('success', 'Profil ditandai masih sesuai.');
    }

    private function actor(Request $request): CareerActor
    {
        /** @var CareerActor $actor */
        $actor = $request->attributes->get(CareerActor::class);

        return $actor;
    }

    private function authorizeExisting(CareerActor $actor, ?CareerProfile $profile, CareerProfileResourcePolicy $policy): void
    {
        if ($profile !== null) {
            abort_unless($policy->view($actor, $profile), 404);
        }
    }

    /** @return array<string, mixed> */
    private function profileData(?CareerProfile $profile): array
    {
        return [
            'professional_name' => $profile?->professional_name,
            'headline' => $profile?->headline,
            'professional_summary' => $profile?->professional_summary,
            'professional_email' => $profile?->professional_email,
            'whatsapp' => $profile?->whatsapp,
            'city' => $profile?->city,
            'linkedin_url' => $profile?->linkedin_url,
            'portfolio_url' => $profile?->portfolio_url,
            'open_to_work' => $profile?->open_to_work ?? false,
            'profile_visibility' => $profile?->profile_visibility ?? 'private',
            'discoverable_by_verified_companies' => $profile?->discoverable_by_verified_companies ?? false,
            'discoverable_by_internal_leadership' => $profile?->discoverable_by_internal_leadership ?? false,
            'visible_in_alumni_directory' => $profile?->visible_in_alumni_directory ?? true,
            'discoverability_updated_at' => $profile?->discoverability_updated_at?->toAtomString(),
            'section_visibility' => $profile?->section_visibility ?? [],
            'has_photo' => $profile?->photo_path !== null,
            'last_confirmed_at' => $profile?->last_confirmed_at?->toAtomString(),
        ];
    }

    /** @return array{percent: int, completed: int, total: int, is_gate: false} */
    private function progress(?CareerProfile $profile): array
    {
        if ($profile === null) {
            return ['percent' => 0, 'completed' => 0, 'total' => 10, 'is_gate' => false];
        }

        $completed = collect([
            filled($profile->professional_name),
            filled($profile->headline),
            filled($profile->professional_summary),
            filled($profile->professional_email) || filled($profile->whatsapp),
            filled($profile->city),
            $profile->educations()->exists(),
            $profile->experiences()->exists(),
            $profile->skills()->exists(),
            $profile->certifications()->exists(),
            $profile->jobPreference()->exists(),
        ])->filter()->count();

        return ['percent' => $completed * 10, 'completed' => $completed, 'total' => 10, 'is_gate' => false];
    }

    /** @return list<array{key: string, label: string, count: int, empty: string}> */
    private function sectionSummaries(?CareerProfile $profile): array
    {
        return array_map(function (ProfileSection $section) use ($profile): array {
            $count = 0;
            if ($profile !== null) {
                $relation = $section->relation();
                $count = $section->singular()
                    ? (int) $profile->{$relation}()->exists()
                    : $profile->{$relation}()->count();
            }

            return [
                'key' => $section->value,
                'label' => $section->label(),
                'count' => $count,
                'empty' => $this->emptyState($section),
            ];
        }, ProfileSection::cases());
    }

    private function emptyState(ProfileSection $section): string
    {
        return match ($section) {
            ProfileSection::Education => 'Belum ada pendidikan',
            ProfileSection::Experience => 'Belum ada pengalaman kerja',
            ProfileSection::Skills => 'Belum ada keterampilan',
            ProfileSection::Certifications => 'Belum ada sertifikasi',
            ProfileSection::Organizations => 'Belum ada pengalaman organisasi',
            ProfileSection::Projects => 'Belum ada proyek atau karya',
            ProfileSection::Publications => 'Belum ada publikasi',
            ProfileSection::Languages => 'Belum ada bahasa',
            ProfileSection::Preferences => 'Preferensi karier belum diisi',
        };
    }
}
