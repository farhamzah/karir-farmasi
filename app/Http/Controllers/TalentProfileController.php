<?php

namespace App\Http\Controllers;

use App\Data\CareerActor;
use App\Models\CareerProfile;
use App\Models\CompanyUser;
use App\Models\LeadershipAssignment;
use App\Talent\TalentAuditRecorder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TalentProfileController extends Controller
{
    public function company(Request $request, string $reference, TalentAuditRecorder $audit): Response
    {
        /** @var CompanyUser $actor */
        $actor = $request->attributes->get(CompanyUser::class);
        $profile = CareerProfile::query()->where('talent_reference', $reference)->where('discoverable_by_verified_companies', true)->firstOrFail();
        $audit->view($actor, $reference);

        return $this->render($profile, 'company');
    }

    public function internal(Request $request, string $reference, TalentAuditRecorder $audit): Response
    {
        /** @var CareerActor $actor */
        $actor = $request->attributes->get(CareerActor::class);
        $assignments = LeadershipAssignment::query()->where('actor_core_user_id', $actor->coreUserId)->where('active', true)->get()->filter->isCurrent();
        $programs = $assignments->flatMap(fn (LeadershipAssignment $assignment) => $assignment->scope_type === 'faculty'
            ? ($assignment->program_references ?? [])
            : [$assignment->scope_reference])->filter()->unique();
        abort_if($programs->isEmpty(), 403);
        $profile = CareerProfile::query()->where('talent_reference', $reference)->where('discoverable_by_internal_leadership', true)
            ->whereHas('educations', fn ($query) => $query->whereIn('program_name', $programs))->firstOrFail();
        $audit->view($actor, $reference);

        return $this->render($profile, 'internal');
    }

    private function render(CareerProfile $profile, string $audience): Response
    {
        $profile->load(['educations', 'experiences', 'skills', 'certifications', 'projects', 'publications', 'languages', 'jobPreference', 'eventRegistrations.event.topics']);

        return Inertia::render('Talent/Profile', ['audience' => $audience, 'profile' => [
            'professional_name' => $profile->professional_name ?: 'Alumni Farmasi', 'headline' => $profile->headline,
            'summary' => $profile->professional_summary, 'city' => $profile->city, 'open_to_work' => $profile->open_to_work,
            'educations' => $profile->educations->map->only(['institution_name', 'program_name', 'degree', 'end_year'])->all(),
            'experiences' => $profile->experiences->map->only(['type', 'organization', 'title', 'location', 'description'])->all(),
            'skills' => $profile->skills->pluck('name')->all(),
            'certifications' => $profile->certifications->map->only(['title', 'issuer', 'issue_date'])->all(),
            'events' => $profile->eventRegistrations->whereNotNull('completed_at')->map(fn ($item) => ['title' => $item->event?->title, 'role' => $item->role, 'topics' => $item->event?->topics?->pluck('label')->all() ?? []])->values()->all(),
            'projects' => $profile->projects->map->only(['title', 'category', 'description'])->all(),
            'publications' => $profile->publications->map->only(['title', 'publication_name', 'published_on'])->all(),
            'languages' => $profile->languages->map->only(['language', 'proficiency'])->all(),
            'preferences' => $profile->jobPreference?->only(['target_roles', 'preferred_locations', 'willing_to_relocate', 'availability_date']),
            'last_confirmed_at' => $profile->last_confirmed_at?->toDateString(),
        ]]);
    }
}
