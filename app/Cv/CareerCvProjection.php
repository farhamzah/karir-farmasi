<?php

namespace App\Cv;

use App\Models\CareerCv;
use App\Models\CareerProfile;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;

final class CareerCvProjection
{
    public function __construct(
        private readonly CvTemplateConfiguration $configuration,
        private readonly CvFieldVisibility $fieldVisibility,
    ) {}

    /** @return array<string, mixed> */
    public function editorProfile(CareerProfile $profile): array
    {
        $profile->load(['educations', 'experiences', 'skills', 'certifications', 'organizations', 'projects', 'publications',
            'languages', 'jobPreference', 'completedEventRegistrations.event.topics', 'eventCertificates.registration.event.topics']);

        return ['name' => $profile->professional_name, 'headline' => $profile->headline, 'summary' => $profile->professional_summary,
            'has_photo' => filled($profile->photo_path), 'sections' => collect(CvSection::cases())->map(fn ($section) => [
                'key' => $section->value, 'label' => $section->label(),
                'items' => $this->sectionItems($profile, $section)->map(fn (Model $item) => ['source_item_id' => $item->id, 'label' => $this->itemLabel($section, $item)])->values()->all(),
            ])->all()];
    }

    /** @return array<string, mixed> */
    public function preview(CareerCv $cv): array
    {
        $cv->load(['profile.educations', 'profile.experiences', 'profile.skills', 'profile.certifications', 'profile.organizations',
            'profile.projects', 'profile.publications', 'profile.languages', 'profile.completedEventRegistrations.event.topics',
            'profile.jobPreference', 'profile.eventCertificates.registration.event.topics', 'templateVersion.template', 'sectionPreferences', 'itemPreferences']);
        $profile = $cv->profile;
        $visibleFields = $this->fieldVisibility->normalized($cv->field_visibility);
        $sections = $cv->sectionPreferences->where('enabled', true)->sortBy('sort_order')->map(function ($preference) use ($cv, $profile) {
            $section = CvSection::from($preference->section_key);
            if ($section === CvSection::Summary) {
                $summary = $cv->custom_summary ?: $profile->professional_summary;

                return filled($summary) ? ['key' => $section->value, 'title' => $preference->display_title ?: $section->label(), 'items' => [['description' => $summary]]] : null;
            }
            $selected = $cv->itemPreferences->where('section_key', $section->value)->where('enabled', true)->sortBy('sort_order');
            $items = $selected->map(fn ($itemPreference) => $this->findSectionItem($profile, $section, (int) $itemPreference->source_item_id))
                ->filter()->map(fn (Model $item) => $this->safeItem($section, $item))->values()->all();

            return $items === [] ? null : ['key' => $section->value, 'title' => $preference->display_title ?: $section->label(), 'items' => $items];
        })->filter()->values()->all();

        return [
            'id' => $cv->id, 'name' => $cv->name, 'professional_name' => $profile->professional_name ?: 'Alumni Farmasi UBP',
            'headline' => $cv->custom_headline ?: $profile->headline,
            'email' => $visibleFields['email'] ? $profile->professional_email : null,
            'whatsapp' => $visibleFields['whatsapp'] ? $profile->whatsapp : null,
            'city' => $visibleFields['city'] ? $profile->city : null,
            'linkedin_url' => $visibleFields['linkedin_url'] ? $profile->linkedin_url : null,
            'portfolio_url' => $visibleFields['portfolio_url'] ? $profile->portfolio_url : null,
            'has_photo' => $visibleFields['photo'] && filled($profile->photo_path),
            'open_to_work' => (bool) $profile->open_to_work,
            'template' => ['key' => $cv->templateVersion->template->key, 'name' => $cv->templateVersion->template->name,
                'version' => $cv->templateVersion->version, 'configuration' => $this->configuration->normalized($cv->templateVersion->configuration)],
            'sections' => $sections,
        ];
    }

    private function itemLabel(CvSection $section, Model $item): string
    {
        return match ($section) {
            CvSection::Education => "$item->program_name · $item->institution_name",
            CvSection::Experience => "$item->title · $item->organization", CvSection::Skills => $item->name,
            CvSection::Certifications => "$item->title · $item->issuer", CvSection::Organizations => "$item->role · $item->organization",
            CvSection::Projects, CvSection::Publications => $item->title, CvSection::Languages => $item->language,
            CvSection::Preferences => 'Preferensi karier',
            CvSection::Events => $item->event->title.' · '.$this->eventRole($item->role),
            CvSection::EventCertificates => $item->registration->event->title.' · '.$item->certificate_number,
            CvSection::Summary => '',
        };
    }

    /** @return array<string, mixed> */
    private function safeItem(CvSection $section, Model $item): array
    {
        $fields = match ($section) {
            CvSection::Education => ['institution_name', 'program_name', 'degree', 'start_year', 'end_year', 'status'],
            CvSection::Experience => ['type', 'organization', 'title', 'location', 'start_date', 'end_date', 'currently_active', 'description'],
            CvSection::Skills => ['name', 'category', 'level'], CvSection::Certifications => ['title', 'issuer', 'issue_date', 'expiry_date', 'credential_id', 'credential_url'],
            CvSection::Organizations => ['organization', 'role', 'start_date', 'end_date', 'description'],
            CvSection::Projects => ['title', 'category', 'description', 'project_url'], CvSection::Publications => ['title', 'publication_name', 'published_on', 'url', 'doi'],
            CvSection::Languages => ['language', 'proficiency'],
            CvSection::Preferences => ['target_roles', 'employment_types', 'preferred_locations', 'willing_to_relocate', 'availability_date'],
            CvSection::Summary => [],
            CvSection::Events, CvSection::EventCertificates => [],
        };

        if ($section === CvSection::Events) {
            return ['title' => $item->event->title, 'event_type' => str($item->event->event_type)->replace('_', ' ')->title()->toString(),
                'organizer' => $item->event->organizer, 'date' => $item->event->starts_at->locale('id')->translatedFormat('d M Y'),
                'role' => $this->eventRole($item->role), 'location' => $item->event->location_text,
                'topics' => $item->event->topics->pluck('label')->implode(', '), 'description' => $item->event->description];
        }
        if ($section === CvSection::EventCertificates) {
            return ['title' => $item->registration->event->title, 'issuer' => $item->registration->event->organizer,
                'issue_date' => $item->issued_at->locale('id')->translatedFormat('d M Y'), 'certificate_number' => $item->certificate_number,
                'status' => 'Terverifikasi'];
        }

        return collect($fields)->mapWithKeys(fn ($field) => [$field => $this->displayValue($field, $item->{$field})])->all();
    }

    private function eventRole(string $role): string
    {
        return ['participant' => 'Peserta', 'committee' => 'Panitia', 'speaker' => 'Pembicara', 'moderator' => 'Moderator',
            'mentor' => 'Mentor / Fasilitator'][$role] ?? str($role)->replace('_', ' ')->title()->toString();
    }

    /** @return EloquentCollection<int, Model> */
    private function sectionItems(CareerProfile $profile, CvSection $section): EloquentCollection
    {
        if ($section->relation() === null) {
            return new EloquentCollection;
        }

        $source = $profile->{$section->relation()};

        if ($source instanceof Model) {
            return new EloquentCollection([$source]);
        }

        if ($source instanceof EloquentCollection) {
            return $source;
        }

        return new EloquentCollection;
    }

    private function findSectionItem(CareerProfile $profile, CvSection $section, int $sourceItemId): ?Model
    {
        return $this->sectionItems($profile, $section)->firstWhere('id', $sourceItemId);
    }

    private function displayValue(string $field, mixed $value): mixed
    {
        if ($field === 'type' && is_string($value)) {
            return match ($value) {
                'internship' => 'Magang / PKPA',
                'work' => 'Pengalaman Kerja',
                'volunteer' => 'Relawan',
                'research' => 'Riset',
                'teaching' => 'Pengajaran',
                'entrepreneurship' => 'Kewirausahaan',
                default => str($value)->replace('_', ' ')->title()->toString(),
            };
        }
        if (in_array($field, ['start_date', 'end_date', 'issue_date', 'expiry_date', 'published_on'], true) && filled($value)) {
            return Carbon::parse($value)->locale('id')->translatedFormat('M Y');
        }
        if ($field === 'availability_date' && filled($value)) {
            return Carbon::parse($value)->locale('id')->translatedFormat('d M Y');
        }
        if ($field === 'willing_to_relocate' && is_bool($value)) {
            return $value ? 'Bersedia relokasi' : 'Belum bersedia relokasi';
        }
        if (is_array($value)) {
            return collect($value)->filter(fn ($item) => filled($item))->implode(', ');
        }

        return $value instanceof \DateTimeInterface ? $value->format('Y-m-d') : $value;
    }
}
