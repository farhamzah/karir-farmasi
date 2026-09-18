<?php

namespace App\Http\Controllers;

use App\Data\CareerActor;
use App\Models\CareerProfile;
use App\Models\CompanyShortlist;
use App\Models\CompanyUser;
use App\Models\LeadershipAssignment;
use App\Models\TalentProfileIndex;
use App\Support\CareerRoleRegistry;
use App\Talent\TalentAuditRecorder;
use App\Talent\TalentSearchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TalentSearchController extends Controller
{
    public function company(Request $request, TalentSearchService $search, TalentAuditRecorder $audit): Response
    {
        /** @var CompanyUser $actor */
        $actor = $request->attributes->get(CompanyUser::class);
        $filters = $this->filters($request);
        $results = $search->search($filters, 'company');
        $audit->search($actor, $filters, $results->count());
        $shortlisted = CompanyShortlist::query()->where('company_id', $actor->company_id)->pluck('career_profile_id')->all();

        return Inertia::render('Talent/Search', [
            'audience' => 'company', 'heading' => 'Temukan talenta farmasi yang relevan.',
            'scope' => 'Kandidat yang memberi izin kepada perusahaan terverifikasi', 'filters' => $filters,
            'results' => $results->map(fn ($index) => $this->card($index, $search, $filters, in_array($index->career_profile_id, $shortlisted, true)))->all(),
        ]);
    }

    public function internal(Request $request, TalentSearchService $search, TalentAuditRecorder $audit): Response
    {
        /** @var CareerActor $actor */
        $actor = $request->attributes->get(CareerActor::class);
        $isAdministrator = in_array(CareerRoleRegistry::Administrator, $actor->roles, true);
        $assignments = LeadershipAssignment::query()->where('actor_core_user_id', $actor->coreUserId)->where('active', true)
            ->where(fn ($query) => $query->whereNull('valid_from')->orWhere('valid_from', '<=', today()))
            ->where(fn ($query) => $query->whereNull('valid_until')->orWhere('valid_until', '>=', today()))->get();
        $filters = $this->filters($request);
        if (! $isAdministrator && $assignments->isEmpty()) {
            return Inertia::render('Talent/Search', [
                'audience' => 'internal', 'heading' => 'Direktori Talenta Farmasi — Internal',
                'scope' => 'Lingkup akses belum ditetapkan oleh administrator SAFA KARIR.',
                'filters' => $filters, 'results' => [], 'accessUnavailable' => true,
            ]);
        }
        $results = $search->search($filters, 'internal', $assignments, $isAdministrator);
        $audit->search($actor, $filters, $results->count());

        return Inertia::render('Talent/Search', [
            'audience' => 'internal', 'heading' => 'Direktori Talenta Farmasi — Internal',
            'scope' => $isAdministrator
                ? 'Seluruh alumni Farmasi UBP · akses administrator'
                : $assignments->map(fn ($item) => $item->scope_label)->unique()->implode(', '), 'filters' => $filters,
            'results' => $results->map(fn ($index) => $this->card($index, $search, $filters, false))->all(),
            'accessUnavailable' => false,
        ]);
    }

    public function shortlist(Request $request, string $reference): RedirectResponse
    {
        /** @var CompanyUser $actor */
        $actor = $request->attributes->get(CompanyUser::class);
        $profile = CareerProfile::query()->where('talent_reference', $reference)->where('discoverable_by_verified_companies', true)->firstOrFail();
        CompanyShortlist::query()->firstOrCreate(['company_id' => $actor->company_id, 'career_profile_id' => $profile->id], ['saved_by_company_user_id' => $actor->id]);

        return back()->with('success', 'Kandidat disimpan ke shortlist perusahaan.');
    }

    public function unshortlist(Request $request, string $reference): RedirectResponse
    {
        /** @var CompanyUser $actor */
        $actor = $request->attributes->get(CompanyUser::class);
        $profile = CareerProfile::query()->where('talent_reference', $reference)->firstOrFail();
        CompanyShortlist::query()->where('company_id', $actor->company_id)->where('career_profile_id', $profile->id)->delete();

        return back()->with('success', 'Kandidat dihapus dari shortlist.');
    }

    /** @return array<string, mixed> */
    private function filters(Request $request): array
    {
        return $request->validate([
            'q' => ['nullable', 'string', 'max:100'], 'skill' => ['nullable', 'string', 'max:100'],
            'experience_type' => ['nullable', 'string', 'max:50'], 'sector' => ['nullable', 'string', 'max:100'],
            'certification' => ['nullable', 'string', 'max:150'], 'event_topic' => ['nullable', 'string', 'max:150'],
            'education_level' => ['nullable', 'string', 'max:80'], 'education_program' => ['nullable', 'string', 'max:150'],
            'graduation_year' => ['nullable', 'integer', 'between:1950,2100'], 'city' => ['nullable', 'string', 'max:100'],
            'preferred_location' => ['nullable', 'string', 'max:100'], 'open_to_work' => ['nullable', 'boolean'],
            'availability_date' => ['nullable', 'date'], 'willing_to_relocate' => ['nullable', 'boolean'],
        ]);
    }

    /** @return array<string, mixed> */
    private function card(TalentProfileIndex $index, TalentSearchService $search, array $filters, bool $shortlisted): array
    {
        return [
            'reference' => $index->profile->talent_reference, 'professional_name' => $index->professional_name,
            'headline' => $index->headline, 'city' => $index->city,
            'education' => collect([$index->education_level, $index->education_program])->filter()->implode(' · '),
            'skills' => array_slice($index->skills ?? [], 0, 6), 'experience' => array_slice($index->sectors ?? [], 0, 2),
            'badges' => array_slice(array_values(array_unique(array_merge($index->certifications ?? [], $index->event_topics ?? []))), 0, 4),
            'open_to_work' => $index->open_to_work, 'last_confirmed_at' => $index->last_confirmed_at?->toDateString(),
            'matched' => $search->matchedTerms($index, (string) ($filters['q'] ?? '')), 'shortlisted' => $shortlisted,
        ];
    }
}
