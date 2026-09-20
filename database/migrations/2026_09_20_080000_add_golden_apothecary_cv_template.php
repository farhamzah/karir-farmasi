<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $templateId = DB::table('cv_templates')->insertGetId([
            'key' => 'cv-07',
            'name' => 'Golden Apothecary',
            'description' => 'CV A4 editorial dengan bidang krem, sidebar arang, foto bundar, dan aksen emas yang elegan.',
            'active' => true,
            'category' => 'premium-editorial',
            'base_template_key' => 'cv-07',
            'display_order' => 70,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('cv_template_versions')->insert([
            'cv_template_id' => $templateId,
            'version' => '1.0.0',
            'configuration' => json_encode([
                'layout' => 'sidebar-main',
                'photo' => 'circle',
                'typography' => 'classic',
                'spacing' => 'compact',
                'header_style' => 'sidebar',
                'section_style' => 'accent',
                'accent' => 'neutral',
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
        $templateId = DB::table('cv_templates')->where('key', 'cv-07')->value('id');
        if ($templateId === null) {
            return;
        }

        $versionIds = DB::table('cv_template_versions')->where('cv_template_id', $templateId)
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')->from('career_cvs')
                    ->whereColumn('career_cvs.cv_template_version_id', 'cv_template_versions.id');
            })->pluck('id');
        DB::table('cv_template_versions')->whereIn('id', $versionIds)->delete();
        DB::table('cv_templates')->where('id', $templateId)->whereNotExists(function ($query): void {
            $query->selectRaw('1')->from('cv_template_versions')
                ->whereColumn('cv_template_versions.cv_template_id', 'cv_templates.id');
        })->delete();
    }
};
