<?php

namespace Tests\Feature;

use App\Models\CareerCv;
use App\Models\CvPublishedRevision;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Support\BuildsCvFixtures;
use Tests\TestCase;

class CvPublishingTest extends TestCase
{
    use BuildsCvFixtures, RefreshDatabase;

    public function test_publish_creates_immutable_revision_and_explicit_update_moves_following_link(): void
    {
        $profile = $this->profile();
        $experience = $profile->experiences()->create([
            'type' => 'work', 'title' => 'Apoteker Klinik', 'organization' => 'Rumah Sakit Sintetis',
            'description' => 'Menangani pelayanan kefarmasian.', 'sort_order' => 0,
        ]);
        $session = ['core_principal' => $this->principal()];
        $this->withSession($session)->post(route('cv.store'), $this->cvPayload($profile));
        $cv = CareerCv::sole();

        $this->withSession($session)->post(route('cv.publish', $cv))->assertRedirect();
        $first = CvPublishedRevision::sole();
        $this->assertSame(1, $first->revision_number);
        $this->assertSame('Apoteker Klinik', $first->snapshot['headline']);
        $this->assertSame('Menangani pelayanan kefarmasian.', collect($first->snapshot['sections'])->firstWhere('key', 'experience')['items'][0]['description']);
        $this->assertSame(64, strlen($first->content_checksum));

        $this->withSession($session)->post(route('cv.shares.store', $cv), [
            'label' => 'Rekruter Sintetis', 'allow_pdf_download' => true, 'follow_latest_published' => true,
        ])->assertRedirect();
        $link = $cv->shareLinks()->sole();
        $token = $link->token();

        $cv->update(['custom_headline' => 'Headline draft baru']);
        $experience->update(['description' => 'Tanggung jawab terbaru dari profil.']);
        $this->get(route('public-cv.show', $token))->assertOk()->assertInertia(fn ($page) => $page
            ->component('Cv/Public')->where('cv.headline', 'Apoteker Klinik'));
        $this->assertSame($first->id, $link->fresh()->current_revision_id);

        $this->withSession($session)->post(route('cv.publish', $cv))->assertRedirect();
        $second = CvPublishedRevision::where('revision_number', 2)->sole();
        $this->assertSame('Apoteker Klinik', $first->fresh()->snapshot['headline']);
        $this->assertSame('Menangani pelayanan kefarmasian.', collect($first->fresh()->snapshot['sections'])->firstWhere('key', 'experience')['items'][0]['description']);
        $this->assertSame('Headline draft baru', $second->snapshot['headline']);
        $this->assertSame('Tanggung jawab terbaru dari profil.', collect($second->snapshot['sections'])->firstWhere('key', 'experience')['items'][0]['description']);
        $this->assertSame($second->id, $link->fresh()->current_revision_id);
        $this->get(route('public-cv.show', $token))->assertOk()->assertInertia(fn ($page) => $page->where('cv.headline', 'Headline draft baru'));

        $this->expectException(\LogicException::class);
        $first->update(['content_checksum' => str_repeat('0', 64)]);
    }

    public function test_publish_is_own_only_and_browser_owner_fields_are_irrelevant(): void
    {
        $owner = $this->profile('owner-a');
        $this->withSession(['core_principal' => $this->principal('owner-a')])->post(route('cv.store'), $this->cvPayload($owner));
        $cv = CareerCv::sole();

        $this->withSession(['core_principal' => $this->principal('owner-b')])->post(route('cv.publish', $cv), [
            'core_user_id' => 'owner-a', 'career_profile_id' => $owner->id,
        ])->assertNotFound();
        foreach (['admin-karir', 'petugas-karir', 'viewer-karir'] as $role) {
            $this->withSession(['core_principal' => $this->principal('staff-'.$role, [$role])])->post(route('cv.publish', $cv))->assertForbidden();
        }
        $this->assertDatabaseCount('cv_published_revisions', 0);
    }

    public function test_photo_is_snapshotted_privately_and_cv_delete_cleans_revision_and_share(): void
    {
        Storage::fake('career_private');
        $profile = $this->profile();
        Storage::disk('career_private')->put('profiles/source/photo.jpg', 'first-photo-bytes');
        $profile->update(['photo_path' => 'profiles/source/photo.jpg']);
        $session = ['core_principal' => $this->principal()];
        $this->withSession($session)->post(route('cv.store'), $this->cvPayload($profile, 'cv-02'));
        $cv = CareerCv::sole();
        $this->withSession($session)->post(route('cv.publish', $cv));
        $revision = CvPublishedRevision::sole();
        $this->assertNotSame($profile->photo_path, $revision->photo_path);
        Storage::disk('career_private')->assertExists($revision->photo_path);

        $this->withSession($session)->post(route('cv.shares.store', $cv), []);
        $link = $cv->shareLinks()->sole();
        $this->get(route('public-cv.photo', $link->token()))->assertOk()->assertStreamedContent('first-photo-bytes');
        Storage::disk('career_private')->put('profiles/source/replacement.jpg', 'replacement-photo');
        $profile->update(['photo_path' => 'profiles/source/replacement.jpg']);
        $this->get(route('public-cv.photo', $link->token()))->assertStreamedContent('first-photo-bytes');

        $snapshotPath = $revision->photo_path;
        $this->withSession($session)->delete(route('cv.destroy', $cv))->assertRedirect();
        Storage::disk('career_private')->assertMissing($snapshotPath);
        $this->assertDatabaseCount('cv_published_revisions', 0);
        $this->assertDatabaseCount('cv_share_links', 0);
    }

    public function test_hidden_photo_is_not_copied_or_served_from_the_public_revision(): void
    {
        Storage::fake('career_private');
        $profile = $this->profile();
        Storage::disk('career_private')->put('profiles/source/private-photo.jpg', 'private-photo-bytes');
        $profile->update(['photo_path' => 'profiles/source/private-photo.jpg']);
        $payload = $this->cvPayload($profile, 'cv-02');
        $payload['field_visibility'] = ['photo' => false, 'city' => true, 'email' => true, 'whatsapp' => false, 'linkedin_url' => false, 'portfolio_url' => false];
        $this->withSession(['core_principal' => $this->principal()])->post(route('cv.store'), $payload)->assertRedirect()->assertSessionHasNoErrors();

        $cv = CareerCv::sole();
        $this->withSession(['core_principal' => $this->principal()])->post(route('cv.publish', $cv))->assertRedirect();
        $revision = CvPublishedRevision::sole();
        $this->assertFalse($revision->snapshot['has_photo']);
        $this->assertNull($revision->photo_path);

        $this->withSession(['core_principal' => $this->principal()])->post(route('cv.shares.store', $cv), []);
        $token = $cv->shareLinks()->sole()->token();
        $this->get(route('public-cv.show', $token))->assertOk()->assertInertia(fn ($page) => $page->where('photoUrl', null));
        $this->get(route('public-cv.photo', $token))->assertNotFound();
        Storage::disk('career_private')->assertExists('profiles/source/private-photo.jpg');
    }
}
