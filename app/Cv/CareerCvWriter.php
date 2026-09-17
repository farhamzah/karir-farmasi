<?php

namespace App\Cv;

use App\Models\CareerCv;
use App\Models\CareerProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CareerCvWriter
{
    public function __construct(
        private readonly CvTemplateCatalog $templates,
        private readonly CvFieldVisibility $fieldVisibility,
    ) {}

    /** @param array<string, mixed> $data */
    public function save(CareerProfile $profile, array $data, ?CareerCv $cv = null): CareerCv
    {
        $requestedVersion = (int) $data['template_version_id'];
        $version = $cv?->cv_template_version_id === $requestedVersion
            ? $this->templates->resolveBound($requestedVersion)
            : $this->templates->resolve($requestedVersion);

        return DB::transaction(function () use ($profile, $data, $cv, $version): CareerCv {
            $cv ??= new CareerCv(['career_profile_id' => $profile->id]);
            if ($cv->exists && $cv->career_profile_id !== $profile->id) {
                abort(404);
            }
            $cv->fill([
                'career_profile_id' => $profile->id, 'cv_template_version_id' => $version->id,
                'name' => $data['name'], 'custom_headline' => $data['custom_headline'] ?? null,
                'custom_summary' => $data['custom_summary'] ?? null,
                'field_visibility' => $this->fieldVisibility->normalized($data['field_visibility'] ?? $cv?->field_visibility),
                'status' => $data['status'],
            ])->save();

            $cv->sectionPreferences()->delete();
            $cv->itemPreferences()->delete();
            foreach ($data['sections'] as $sectionData) {
                $section = CvSection::from($sectionData['key']);
                $cv->sectionPreferences()->create([
                    'section_key' => $section->value, 'enabled' => $sectionData['enabled'],
                    'sort_order' => $sectionData['sort_order'], 'display_title' => $sectionData['display_title'] ?? null,
                ]);
                $items = $sectionData['items'] ?? [];
                $this->assertOwnedItems($profile, $section, $items);
                foreach ($items as $item) {
                    $cv->itemPreferences()->create([
                        'section_key' => $section->value, 'source_item_id' => $item['source_item_id'],
                        'enabled' => $item['enabled'], 'sort_order' => $item['sort_order'],
                    ]);
                }
            }

            return $cv->fresh(['templateVersion.template', 'sectionPreferences', 'itemPreferences']);
        });
    }

    /** @param list<array<string, mixed>> $items */
    private function assertOwnedItems(CareerProfile $profile, CvSection $section, array $items): void
    {
        if ($section->relation() === null && $items !== []) {
            throw ValidationException::withMessages(['sections' => 'Ringkasan tidak menerima item sumber.']);
        }
        if ($section->relation() === null) {
            return;
        }
        if ($items === []) {
            return;
        }
        $ids = collect($items)->pluck('source_item_id')->map(fn ($id) => (int) $id);
        if ($ids->duplicates()->isNotEmpty()) {
            throw ValidationException::withMessages(['sections' => 'Item sumber tidak boleh berulang.']);
        }
        $owned = $profile->{$section->relation()}()->whereKey($ids)->count();
        if ($owned !== $ids->count()) {
            throw ValidationException::withMessages(['sections' => 'Salah satu item profil tidak dimiliki kandidat.']);
        }
    }
}
