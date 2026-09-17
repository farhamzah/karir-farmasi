<?php

namespace Database\Factories;

use App\Models\CvTemplate;
use App\Models\CvTemplateVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CvTemplateVersion>
 */
class CvTemplateVersionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cv_template_id' => CvTemplate::factory(),
            'version' => '1.0.0',
            'configuration' => ['layout' => 'single-column', 'photo' => 'hidden', 'typography' => 'classic', 'spacing' => 'comfortable', 'section_style' => 'rule'],
            'status' => 'published',
            'published_at' => now(),
        ];
    }
}
