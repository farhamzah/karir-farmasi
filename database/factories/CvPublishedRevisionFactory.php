<?php

namespace Database\Factories;

use App\Models\CareerCv;
use App\Models\CvPublishedRevision;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CvPublishedRevision>
 */
class CvPublishedRevisionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'public_id' => (string) Str::uuid(),
            'career_cv_id' => CareerCv::factory(),
            'cv_template_version_id' => fn (array $attributes) => CareerCv::findOrFail($attributes['career_cv_id'])->cv_template_version_id,
            'revision_number' => 1,
            'snapshot' => ['professional_name' => 'Alumni Sintetis', 'template' => ['key' => 'cv-01'], 'sections' => []],
            'content_checksum' => hash('sha256', 'fixture'),
            'published_at' => now(),
        ];
    }
}
