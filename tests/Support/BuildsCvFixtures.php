<?php

namespace Tests\Support;

use App\Cv\CvSection;
use App\Models\CareerProfile;
use App\Models\CvTemplateVersion;

trait BuildsCvFixtures
{
    protected function principal(string $id = 'core-cv-owner', array $roles = ['kandidat-karir']): array
    {
        return ['issuer' => 'https://fixture.invalid', 'subject' => 'fixture:'.$id, 'core_user_id' => $id,
            'display_name' => 'Alya Nūr Sintetis', 'email' => 'login@fixture.invalid', 'active' => true,
            'app_code' => 'karir-farmasi', 'has_app_access' => true, 'roles' => $roles, 'program_ids' => [],
            'verified_at' => '2026-09-12T00:00:00+07:00', 'synthetic' => true];
    }

    protected function profile(string $id = 'core-cv-owner'): CareerProfile
    {
        $profile = CareerProfile::factory()->create(['core_user_id' => $id, 'professional_name' => 'Alya Nūr Sintetis',
            'headline' => 'Apoteker berorientasi pada keselamatan pasien', 'professional_summary' => 'Alumni Farmasi UBP dengan ketelitian dan kepedulian dalam pelayanan.',
            'professional_email' => 'alya@fixture.invalid', 'whatsapp' => '0800000000', 'city' => 'Karawang']);
        $profile->skills()->create(['name' => 'Farmasi klinis', 'sort_order' => 0]);
        $profile->skills()->create(['name' => 'Komunikasi pasien', 'sort_order' => 1]);
        $profile->jobPreference()->create([
            'target_roles' => ['Apoteker klinik', 'Regulatory affairs'],
            'employment_types' => ['Full-time'],
            'preferred_locations' => ['Karawang', 'Bekasi'],
            'willing_to_relocate' => true,
            'availability_date' => '2026-10-01',
        ]);

        return $profile;
    }

    /** @return array<string, mixed> */
    protected function cvPayload(CareerProfile $profile, string $template = 'cv-01', string $name = 'CV Klinik'): array
    {
        $version = CvTemplateVersion::whereHas('template', fn ($query) => $query->where('key', $template))
            ->where('status', 'published')->latest('published_at')->latest('id')->firstOrFail();

        return ['name' => $name, 'template_version_id' => $version->id, 'custom_headline' => 'Apoteker Klinik',
            'custom_summary' => 'Ringkasan khusus yang relevan.', 'status' => 'draft',
            'sections' => collect(CvSection::cases())->values()->map(function (CvSection $section, int $order) use ($profile): array {
                $query = $section->relation() ? $profile->{$section->relation()}() : null;
                $orderColumn = in_array($section, [CvSection::Events, CvSection::EventCertificates, CvSection::Preferences], true) ? 'id' : 'sort_order';
                $items = $query ? $query->orderBy($orderColumn)->get()->values()->map(fn ($item, $itemOrder) => [
                    'source_item_id' => $item->id, 'enabled' => true, 'sort_order' => $itemOrder,
                ])->all() : [];

                return ['key' => $section->value, 'enabled' => true, 'sort_order' => $order, 'display_title' => null, 'items' => $items];
            })->all()];
    }
}
