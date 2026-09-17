<?php

namespace App\Talent;

use App\Models\CareerProfile;
use App\Models\TalentProfileIndex;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final class TalentIndexBuilder
{
    public function rebuild(CareerProfile $profile): TalentProfileIndex
    {
        $profile->load([
            'educations', 'experiences', 'skills', 'certifications', 'projects',
            'publications', 'languages', 'jobPreference',
            'eventRegistrations.event.topics', 'eventRegistrations.certificate',
        ]);

        $education = $profile->educations->sortByDesc('end_year')->first();
        $preference = $profile->jobPreference;
        $completedEvents = $profile->eventRegistrations->filter(
            fn ($registration) => $registration->completed_at !== null,
        );

        $experienceTypes = $this->values($profile->experiences, 'type');
        $sectors = $this->unique(array_merge(
            $this->values($profile->experiences, 'organization'),
            $this->values($profile->experiences, 'title'),
        ));
        $skills = $this->values($profile->skills, 'name');
        $certifications = $this->unique(array_merge(
            $this->values($profile->certifications, 'title'),
            $completedEvents->filter(fn ($registration) => $registration->certificate !== null && $registration->certificate->revoked_at === null)
                ->map(fn ($registration) => $registration->event?->title)->filter()->all(),
        ));
        $eventTopics = $this->unique($completedEvents->flatMap(
            fn ($registration) => $registration->event?->topics?->pluck('label')->all() ?? [],
        )->all());
        $eventRoles = $this->unique($completedEvents->pluck('role')->filter()->all());
        $projectTags = $this->unique(array_merge(
            $this->values($profile->projects, 'category'),
            $this->values($profile->projects, 'title'),
        ));
        $publicationKeywords = $this->unique(array_merge(
            $this->values($profile->publications, 'title'),
            $this->values($profile->publications, 'publication_name'),
        ));
        $languages = $this->values($profile->languages, 'language');
        $targetRoles = $preference?->target_roles ?? [];
        $preferredLocations = $preference?->preferred_locations ?? [];
        $terms = $this->unique(array_merge(
            [$profile->professional_name, $profile->headline, $profile->professional_summary, $profile->city,
                $education?->degree, $education?->program_name],
            $experienceTypes, $sectors, $skills, $this->values($profile->skills, 'category'), $certifications, $eventTopics, $eventRoles,
            $projectTags, $publicationKeywords, $languages, $targetRoles, $preferredLocations,
            $this->values($profile->experiences, 'description'),
        ));

        return TalentProfileIndex::query()->updateOrCreate(
            ['career_profile_id' => $profile->id],
            [
                'professional_name' => filled($profile->professional_name) ? $profile->professional_name : 'Alumni Farmasi',
                'headline' => $profile->headline,
                'city' => $profile->city,
                'education_level' => $education?->degree,
                'education_program' => $education?->program_name,
                'graduation_year' => $education?->end_year,
                'experience_types' => $experienceTypes,
                'sectors' => $sectors,
                'skills' => $skills,
                'certifications' => $certifications,
                'event_topics' => $eventTopics,
                'event_roles' => $eventRoles,
                'project_tags' => $projectTags,
                'publication_keywords' => $publicationKeywords,
                'languages' => $languages,
                'target_roles' => $targetRoles,
                'preferred_locations' => $preferredLocations,
                'normalized_terms' => $this->normalize(implode(' | ', $terms)),
                'open_to_work' => (bool) $profile->open_to_work,
                'willing_to_relocate' => $preference?->willing_to_relocate ?? false,
                'availability_date' => $preference?->availability_date,
                'last_confirmed_at' => $profile->last_confirmed_at,
            ],
        );
    }

    /** @return list<string> */
    public function aliases(string $term): array
    {
        $normalized = $this->normalize($term);
        $groups = [
            ['cpob', 'gmp'], ['rumah sakit', 'rs'], ['quality assurance', 'qa'],
            ['quality control', 'qc'], ['sistem jaminan produk halal', 'sjph', 'halal'],
            ['regulatory affairs', 'regulatory'], ['pedagang besar farmasi', 'pbf'],
        ];

        foreach ($groups as $group) {
            if (in_array($normalized, $group, true)) {
                return $group;
            }
        }

        return [$normalized];
    }

    public function normalize(string $value): string
    {
        return Str::of($value)->lower()->ascii()->replaceMatches('/[^a-z0-9]+/', ' ')->squish()->toString();
    }

    /** @param Collection<int, mixed> $items
     * @return list<string>
     */
    private function values(Collection $items, string $key): array
    {
        return $this->unique($items->pluck($key)->filter(fn ($value) => is_string($value) && filled($value))->all());
    }

    /** @param array<int, mixed> $values
     * @return list<string>
     */
    private function unique(array $values): array
    {
        return array_values(array_unique(array_map('strval', array_filter($values, fn ($value) => filled($value)))));
    }
}
