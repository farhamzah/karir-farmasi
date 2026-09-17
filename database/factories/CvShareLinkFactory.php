<?php

namespace Database\Factories;

use App\Models\CvPublishedRevision;
use App\Models\CvShareLink;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

/**
 * @extends Factory<CvShareLink>
 */
class CvShareLinkFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $token = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');

        return [
            'public_id' => (string) Str::uuid(),
            'current_revision_id' => CvPublishedRevision::factory(),
            'career_cv_id' => fn (array $attributes) => CvPublishedRevision::findOrFail($attributes['current_revision_id'])->career_cv_id,
            'token_hash' => hash('sha256', $token),
            'token_ciphertext' => Crypt::encryptString($token),
            'active' => true,
            'follow_latest_published' => true,
            'allow_pdf_download' => false,
        ];
    }
}
