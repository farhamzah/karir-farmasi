<?php

namespace App\Talent;

use App\Models\LeadershipAssignment;
use App\Models\TalentProfileIndex;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class TalentSearchService
{
    public function __construct(private readonly TalentIndexBuilder $indexBuilder) {}

    /** @param array<string, mixed> $filters
     * @param  Collection<int, LeadershipAssignment>|null  $assignments
     * @return Collection<int, TalentProfileIndex>
     */
    public function search(array $filters, string $audience, ?Collection $assignments = null, bool $unrestrictedInternal = false): Collection
    {
        $query = TalentProfileIndex::query()->with('profile:id,talent_reference,discoverable_by_verified_companies,discoverable_by_internal_leadership');
        $flag = $audience === 'company' ? 'discoverable_by_verified_companies' : 'discoverable_by_internal_leadership';
        if (! ($audience === 'internal' && $unrestrictedInternal)) {
            $query->whereHas('profile', fn (Builder $builder) => $builder->where($flag, true));
        }

        if ($audience === 'internal' && ! $unrestrictedInternal) {
            $programs = $assignments?->filter->isCurrent()->flatMap(fn (LeadershipAssignment $assignment) => $assignment->scope_type === 'faculty'
                ? ($assignment->program_references ?? [])
                : [$assignment->scope_reference])->filter()->unique()->values() ?? collect();
            $query->whereIn('education_program', $programs);
        }

        $this->applyKeyword($query, (string) ($filters['q'] ?? ''));
        foreach (['skill' => 'skills', 'certification' => 'certifications', 'event_topic' => 'event_topics', 'experience_type' => 'experience_types', 'sector' => 'sectors'] as $input => $column) {
            if (filled($filters[$input] ?? null)) {
                $query->whereJsonContains($column, $filters[$input]);
            }
        }
        foreach (['education_program', 'education_level', 'city'] as $column) {
            if (filled($filters[$column] ?? null)) {
                $query->where($column, 'like', '%'.addcslashes((string) $filters[$column], '%_\\').'%');
            }
        }
        if (filled($filters['preferred_location'] ?? null)) {
            $query->whereJsonContains('preferred_locations', $filters['preferred_location']);
        }
        foreach (['graduation_year', 'availability_date'] as $column) {
            if (filled($filters[$column] ?? null)) {
                $query->where($column, $filters[$column]);
            }
        }
        foreach (['open_to_work', 'willing_to_relocate'] as $column) {
            if (array_key_exists($column, $filters) && $filters[$column] !== null && $filters[$column] !== '') {
                $query->where($column, (bool) $filters[$column]);
            }
        }

        return $query->orderByDesc('last_confirmed_at')->limit(50)->get();
    }

    /** @return list<string> */
    public function matchedTerms(TalentProfileIndex $index, string $keyword): array
    {
        if (blank($keyword)) {
            return [];
        }

        return collect($this->indexBuilder->aliases($keyword))
            ->filter(fn (string $term) => str_contains($index->normalized_terms, $term))
            ->map(fn (string $term) => strtoupper($term))
            ->values()->all();
    }

    private function applyKeyword(Builder $query, string $keyword): void
    {
        if (blank($keyword)) {
            return;
        }

        $aliases = $this->indexBuilder->aliases($keyword);
        $query->where(function (Builder $builder) use ($aliases): void {
            foreach ($aliases as $alias) {
                $builder->orWhere('normalized_terms', 'like', '%'.addcslashes($alias, '%_\\').'%');
            }
        });
    }
}
