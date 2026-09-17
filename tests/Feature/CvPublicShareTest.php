<?php

namespace Tests\Feature;

use App\Cv\CvPublisher;
use App\Cv\CvShareLinks;
use App\Models\CareerCv;
use App\Models\CvShareLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\Support\BuildsCvFixtures;
use Tests\TestCase;

class CvPublicShareTest extends TestCase
{
    use BuildsCvFixtures, RefreshDatabase;

    public function test_public_page_needs_no_login_and_exposes_only_snapshot_with_security_headers(): void
    {
        [$cv, $link, $token] = $this->publishedShare();
        $response = $this->get(route('public-cv.show', $token));

        $response->assertOk()->assertHeader('X-Robots-Tag', 'noindex, nofollow')->assertHeader('Referrer-Policy', 'no-referrer')
            ->assertInertia(fn ($page) => $page->component('Cv/Public')
                ->where('cv.professional_name', 'Alya Nūr Sintetis')
                ->missing('cv.id')->missing('cv.core_user_id')->missing('cv.registration_status'));
        $content = $response->getContent();
        $this->assertStringNotContainsString('login@fixture.invalid', $content);
        $this->assertStringNotContainsString('core-cv-owner', $content);
        $this->assertSame(1, $link->fresh()->view_count);
        $this->assertNotNull($link->fresh()->last_viewed_at);
        $this->assertFalse(Schema::hasColumn('cv_share_links', 'ip_address'));
        $this->assertFalse(Schema::hasColumn('cv_share_links', 'user_agent'));
    }

    public function test_token_is_opaque_and_disable_expiry_and_rotation_revoke_access(): void
    {
        [$cv, $link, $token] = $this->publishedShare();
        $this->assertGreaterThanOrEqual(43, strlen($token));
        $this->assertStringNotContainsString('core-cv-owner', $token);
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9_-]{43}$/', $token);
        $this->assertSame('/s/'.$token, parse_url(route('public-cv.show', $token), PHP_URL_PATH));

        $link->update(['expires_at' => now()->subSecond()]);
        $this->get(route('public-cv.show', $token))->assertNotFound();
        $link->update(['expires_at' => null]);

        app(CvShareLinks::class)->rotate($link);
        $newToken = $link->fresh()->token();
        $this->assertNotSame($token, $newToken);
        $this->get(route('public-cv.show', $token))->assertNotFound();
        $this->get(route('public-cv.show', $newToken))->assertOk();

        $link->update(['active' => false]);
        $this->get(route('public-cv.show', $newToken))->assertNotFound();
    }

    public function test_share_mutations_are_own_only_and_require_an_explicit_publish(): void
    {
        $profile = $this->profile('owner-a');
        $this->withSession(['core_principal' => $this->principal('owner-a')])->post(route('cv.store'), $this->cvPayload($profile));
        $cv = CareerCv::sole();
        $this->withSession(['core_principal' => $this->principal('owner-a')])->post(route('cv.shares.store', $cv), [])->assertStatus(422);

        app(CvPublisher::class)->publish($cv);
        $link = app(CvShareLinks::class)->create($cv, []);
        $foreign = ['core_principal' => $this->principal('owner-b')];
        $this->withSession($foreign)->put(route('cv.shares.update', [$cv, $link->public_id]), ['active' => false])->assertNotFound();
        $this->withSession($foreign)->post(route('cv.shares.rotate', [$cv, $link->public_id]))->assertNotFound();
        $this->withSession($foreign)->delete(route('cv.shares.destroy', [$cv, $link->public_id]))->assertNotFound();
        $this->assertTrue($link->fresh()->active);
    }

    public function test_private_preview_does_not_crash_when_an_existing_share_token_cannot_be_decrypted(): void
    {
        $profile = $this->profile();
        $this->withSession(['core_principal' => $this->principal()])->post(route('cv.store'), $this->cvPayload($profile));
        $cv = CareerCv::sole();
        app(CvPublisher::class)->publish($cv);
        $link = app(CvShareLinks::class)->create($cv, ['label' => 'Token lama']);
        $link->forceFill(['token_ciphertext' => 'ciphertext-dari-app-key-lama'])->save();

        $this->withSession(['core_principal' => $this->principal()])
            ->get(route('cv.preview', $cv))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Cv/Preview')
                ->where('shareLinks.0.label', 'Token lama')
                ->where('shareLinks.0.token_available', false)
                ->where('shareLinks.0.url', null));
    }

    /** @return array{CareerCv, CvShareLink, string} */
    private function publishedShare(bool $allowPdf = true): array
    {
        $profile = $this->profile();
        $this->withSession(['core_principal' => $this->principal()])->post(route('cv.store'), $this->cvPayload($profile));
        $cv = CareerCv::sole();
        app(CvPublisher::class)->publish($cv);
        $link = app(CvShareLinks::class)->create($cv, ['allow_pdf_download' => $allowPdf]);

        return [$cv, $link, $link->token()];
    }
}
