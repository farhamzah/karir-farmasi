<?php

namespace App\Cv;

use App\Models\CareerCv;
use App\Models\CvShareLink;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

final class CvShareLinks
{
    /** @param array<string, mixed> $data */
    public function create(CareerCv $cv, array $data): CvShareLink
    {
        $token = $this->newToken();
        $revision = $cv->latestPublishedRevision()->firstOrFail();

        return $cv->shareLinks()->create([
            'public_id' => (string) Str::uuid(),
            'current_revision_id' => $revision->id,
            'token_hash' => hash('sha256', $token),
            'token_ciphertext' => Crypt::encryptString($token),
            'label' => $data['label'] ?? null,
            'active' => true,
            'follow_latest_published' => $data['follow_latest_published'] ?? true,
            'allow_pdf_download' => $data['allow_pdf_download'] ?? false,
            'expires_at' => $data['expires_at'] ?? null,
        ]);
    }

    public function rotate(CvShareLink $link): CvShareLink
    {
        $token = $this->newToken();
        $link->forceFill([
            'token_hash' => hash('sha256', $token),
            'token_ciphertext' => Crypt::encryptString($token),
            'active' => true,
            'rotated_at' => now(),
        ])->save();

        return $link->refresh();
    }

    public function resolve(string $token): CvShareLink
    {
        $link = CvShareLink::query()->with('revision')->where('token_hash', hash('sha256', $token))->first();
        abort_if($link === null || ! $link->accessible(), 404);

        return $link;
    }

    public function publicUrl(CvShareLink $link): ?string
    {
        $token = $link->safeToken();
        if ($token === null) {
            return null;
        }

        $name = (string) ($link->revision?->snapshot['professional_name'] ?? 'alumni-farmasi');
        $slug = Str::slug($name) ?: 'alumni-farmasi';

        return route('public-cv.named', ['slug' => $slug, 'token' => $token]);
    }

    private function newToken(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }
}
