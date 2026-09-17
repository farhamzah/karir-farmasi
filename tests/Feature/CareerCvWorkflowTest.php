<?php

namespace Tests\Feature;

use App\Models\CareerCv;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\BuildsCvFixtures;
use Tests\TestCase;

class CareerCvWorkflowTest extends TestCase
{
    use BuildsCvFixtures, RefreshDatabase;

    public function test_candidate_creates_multiple_cvs_from_one_canonical_profile(): void
    {
        $profile = $this->profile();
        $session = ['core_principal' => $this->principal()];
        $this->withSession($session)->post(route('cv.store'), $this->cvPayload($profile, 'cv-01', 'CV Klinik'))->assertRedirect();
        $this->withSession($session)->post(route('cv.store'), $this->cvPayload($profile, 'cv-02', 'CV Industri'))->assertRedirect();
        $this->assertCount(2, $profile->cvs);
        $this->assertDatabaseCount('career_profiles', 1);
        $this->assertEqualsCanonicalizing(['cv-01', 'cv-02'], CareerCv::with('templateVersion.template')->get()->pluck('templateVersion.template.key')->all());
    }

    public function test_selection_order_template_switch_duplicate_and_delete_do_not_mutate_profile(): void
    {
        $profile = $this->profile();
        $session = ['core_principal' => $this->principal()];
        $before = $profile->only(['headline', 'professional_summary']);
        $payload = $this->cvPayload($profile);
        $payload['sections'][3]['items'][0]['enabled'] = false;
        $payload['sections'][3]['items'] = array_reverse($payload['sections'][3]['items']);
        $this->withSession($session)->post(route('cv.store'), $payload);
        $cv = CareerCv::sole();
        $switched = $payload;
        $switched['template_version_id'] = $this->cvPayload($profile, 'cv-02')['template_version_id'];
        $switched['name'] = 'CV Industri Farmasi';
        $this->withSession($session)->put(route('cv.update', $cv), $switched)->assertRedirect();
        $this->withSession($session)->put(route('cv.update', $cv), $payload)->assertRedirect();
        $this->assertSame($before, $profile->fresh()->only(['headline', 'professional_summary']));
        $this->assertSame($payload['template_version_id'], $cv->fresh()->cv_template_version_id);
        $this->assertSame('CV Klinik', $cv->fresh()->name);
        $this->assertFalse($cv->fresh()->itemPreferences()->where('section_key', 'skills')->orderBy('sort_order')->first()->enabled);
        $this->withSession($session)->post(route('cv.duplicate', $cv))->assertRedirect();
        $copy = CareerCv::whereKeyNot($cv->id)->sole();
        $this->assertSame($cv->itemPreferences()->count(), $copy->itemPreferences()->count());
        $this->withSession($session)->delete(route('cv.destroy', $copy))->assertRedirect();
        $this->assertDatabaseCount('career_cvs', 1);
    }
}
