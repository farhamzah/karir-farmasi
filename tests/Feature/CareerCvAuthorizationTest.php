<?php

namespace Tests\Feature;

use App\Models\CareerCv;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\BuildsCvFixtures;
use Tests\TestCase;

class CareerCvAuthorizationTest extends TestCase
{
    use BuildsCvFixtures, RefreshDatabase;

    public function test_cv_routes_require_candidate_capability(): void
    {
        $this->get(route('cv.index'))->assertRedirect(route('login'));
        foreach (['admin-karir', 'petugas-karir', 'viewer-karir'] as $role) {
            $this->withSession(['core_principal' => $this->principal('staff', [$role])])->get(route('cv.index'))->assertForbidden();
        }
    }

    public function test_other_owner_cannot_read_write_duplicate_or_delete_cv(): void
    {
        $owner = $this->profile('owner-a');
        $this->withSession(['core_principal' => $this->principal('owner-a')])->post(route('cv.store'), $this->cvPayload($owner));
        $cv = CareerCv::sole();
        $session = ['core_principal' => $this->principal('owner-b')];
        $this->withSession($session)->get(route('cv.preview', $cv))->assertNotFound();
        $this->withSession($session)->get(route('cv.edit', $cv))->assertNotFound();
        $this->withSession($session)->put(route('cv.update', $cv), $this->cvPayload($owner))->assertNotFound();
        $this->withSession($session)->post(route('cv.duplicate', $cv))->assertNotFound();
        $this->withSession($session)->delete(route('cv.destroy', $cv))->assertNotFound();
    }

    public function test_spoofed_profile_is_ignored_and_foreign_source_item_is_rejected(): void
    {
        $owner = $this->profile('owner-a');
        $foreign = $this->profile('owner-b');
        $payload = $this->cvPayload($owner);
        $payload['career_profile_id'] = $foreign->id;
        $payload['sections'][3]['items'][] = ['source_item_id' => $foreign->skills()->firstOrFail()->id, 'enabled' => true, 'sort_order' => 50];
        $this->withSession(['core_principal' => $this->principal('owner-a')])->post(route('cv.store'), $payload)->assertSessionHasErrors('sections');
        $this->assertDatabaseCount('career_cvs', 0);
    }
}
