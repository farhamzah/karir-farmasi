<?php

namespace App\Operational;

use App\Data\CareerActor;
use App\Models\CareerEventCertificate;
use App\Models\CareerEventRegistration;
use App\Models\CareerJob;
use App\Models\CareerJobApplication;
use App\Models\CareerProfile;
use App\Models\Company;
use App\Models\EmployerFeedback;
use App\Models\LeadershipAssignment;
use App\Models\TracerSubmission;
use Illuminate\Database\Eloquent\Builder;

class OperationalMetrics
{
    /** @return array<string, int|float|string|null> */
    public function forActor(CareerActor $actor): array
    {
        $profileIds = $this->profileIds($actor);
        $profiles = CareerProfile::query()->when($profileIds !== null, fn (Builder $query) => $query->whereIn('id', $profileIds));
        $applications = CareerJobApplication::query()->when($profileIds !== null, fn (Builder $query) => $query->whereIn('career_profile_id', $profileIds));
        $tracer = TracerSubmission::query()->when($profileIds !== null, fn (Builder $query) => $query->whereIn('career_profile_id', $profileIds));
        $events = CareerEventRegistration::query()->when($profileIds !== null, fn (Builder $query) => $query->whereIn('career_profile_id', $profileIds));
        $totalProfiles = (clone $profiles)->count();
        $submittedTracer = (clone $tracer)->where('status', 'submitted')->count();

        return [
            'scope' => $profileIds === null ? 'Semua program yang diizinkan' : 'Assignment program aktif',
            'candidate_profiles' => $totalProfiles,
            'profiles_fresh' => (clone $profiles)->where('last_confirmed_at', '>=', now()->subMonths(6))->count(),
            'profiles_stale' => (clone $profiles)->where(fn (Builder $query) => $query->whereNull('last_confirmed_at')->orWhere('last_confirmed_at', '<', now()->subMonths(6)))->count(),
            'tracer_submitted' => $submittedTracer,
            'tracer_response_rate' => $totalProfiles > 0 ? round(($submittedTracer / $totalProfiles) * 100, 1) : 0,
            'jobs_active' => CareerJob::query()->where('status', 'published')->where(fn (Builder $query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))->count(),
            'jobs_expired' => CareerJob::query()->where('status', 'expired')->count(),
            'applications_total' => (clone $applications)->count(),
            'applications_interview' => (clone $applications)->where('status', 'interview')->count(),
            'applications_offer' => (clone $applications)->whereIn('status', ['offer', 'offer_accepted'])->count(),
            'hires_started' => (clone $applications)->where('status', 'started')->count(),
            'companies_active' => Company::query()->where('active', true)->where('verification_status', 'verified')->count(),
            'event_participations' => (clone $events)->whereNotNull('attended_at')->count(),
            'certificates_issued' => CareerEventCertificate::query()
                ->when($profileIds !== null, fn (Builder $query) => $query->whereHas('registration', fn (Builder $registration) => $registration->whereIn('career_profile_id', $profileIds)))
                ->whereNull('revoked_at')->count(),
            'employer_feedback_count' => EmployerFeedback::query()->when($profileIds !== null, fn (Builder $query) => $query->whereIn('career_profile_id', $profileIds))->count(),
        ];
    }

    /** @return list<int>|null */
    private function profileIds(CareerActor $actor): ?array
    {
        if (! in_array('viewer-karir', $actor->roles, true)) {
            return null;
        }

        $assignments = LeadershipAssignment::query()->where('actor_core_user_id', $actor->coreUserId)->where('active', true)->get()->filter->isCurrent();
        // A viewer without a local assignment receives an empty aggregate.
        // This preserves the read-only dashboard while never widening scope implicitly.
        if ($assignments->isEmpty()) {
            return [];
        }
        $programs = $assignments->flatMap(fn (LeadershipAssignment $assignment) => $assignment->scope_type === 'faculty'
            ? ($assignment->program_references ?? [])
            : [$assignment->scope_reference])->filter()->unique()->values();

        return CareerProfile::query()->whereHas('educations', fn (Builder $query) => $query->whereIn('program_name', $programs))->pluck('id')->all();
    }
}
