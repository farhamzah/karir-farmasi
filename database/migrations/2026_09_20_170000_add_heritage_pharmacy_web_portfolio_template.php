<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $templateId = DB::table('cv_templates')->insertGetId([
            'key' => 'cv-11',
            'name' => 'Heritage Pharmacy Portfolio',
            'description' => 'Website portfolio premium bernuansa warm ivory dan heritage green dengan hero tiga panel, credential bar, serta grid profesional yang kaya informasi.',
            'active' => true,
            'category' => 'web-portfolio',
            'base_template_key' => 'cv-11',
            'display_order' => 110,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('cv_template_versions')->insert([
            'cv_template_id' => $templateId,
            'version' => '1.0.0',
            'configuration' => json_encode([
                'layout' => 'split',
                'photo' => 'oval',
                'typography' => 'classic',
                'spacing' => 'balanced',
                'header_style' => 'hero',
                'section_style' => 'rule',
                'accent' => 'soft',
                'page_padding' => 'compact',
            ], JSON_THROW_ON_ERROR),
            'status' => 'published',
            'published_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        $templateId = DB::table('cv_templates')->where('key', 'cv-11')->value('id');
        if ($templateId === null) {
            return;
        }

        $versionIds = DB::table('cv_template_versions')->where('cv_template_id', $templateId)
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')->from('career_cvs')->whereColumn('career_cvs.cv_template_version_id', 'cv_template_versions.id');
            })->pluck('id');
        DB::table('cv_template_versions')->whereIn('id', $versionIds)->delete();
        DB::table('cv_templates')->where('id', $templateId)->whereNotExists(function ($query): void {
            $query->selectRaw('1')->from('cv_template_versions')->whereColumn('cv_template_versions.cv_template_id', 'cv_templates.id');
        })->delete();
    }
};
