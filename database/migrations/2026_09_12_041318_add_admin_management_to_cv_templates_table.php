<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cv_templates', function (Blueprint $table) {
            $table->string('base_template_key', 30)->nullable()->after('category');
            $table->unsignedSmallInteger('display_order')->default(0)->after('base_template_key');
        });

        DB::table('cv_templates')->where('key', 'cv-01')->update(['base_template_key' => 'cv-01', 'display_order' => 10]);
        DB::table('cv_templates')->where('key', 'cv-02')->update(['base_template_key' => 'cv-02', 'display_order' => 20]);

        $now = now();
        $templates = [
            ['key' => 'cv-03', 'name' => 'Fresh Graduate', 'description' => 'Sorotan ramah dan ringkas untuk pendidikan, praktik, organisasi, proyek, dan sertifikasi.', 'category' => 'early-career', 'order' => 30,
                'configuration' => ['layout' => 'split', 'photo' => 'circle', 'typography' => 'modern', 'spacing' => 'balanced', 'header_style' => 'hero', 'section_style' => 'accent', 'accent' => 'soft', 'page_padding' => 'standard']],
            ['key' => 'cv-04', 'name' => 'Industri & Praktik', 'description' => 'Hierarki tegas untuk pengalaman kerja, PKPA/KP, kompetensi teknis, dan sertifikasi.', 'category' => 'industry', 'order' => 40,
                'configuration' => ['layout' => 'sidebar-main', 'photo' => 'oval', 'typography' => 'modern', 'spacing' => 'compact', 'header_style' => 'sidebar', 'section_style' => 'accent', 'accent' => 'brand', 'page_padding' => 'compact']],
            ['key' => 'cv-05', 'name' => 'Akademik & Riset', 'description' => 'Komposisi akademik bersih untuk pendidikan, riset, publikasi, presentasi, dan proyek.', 'category' => 'academic', 'order' => 50,
                'configuration' => ['layout' => 'single-column', 'photo' => 'hidden', 'typography' => 'academic', 'spacing' => 'compact', 'header_style' => 'classic', 'section_style' => 'plain', 'accent' => 'neutral', 'page_padding' => 'standard']],
        ];

        foreach ($templates as $template) {
            $templateId = DB::table('cv_templates')->insertGetId([
                'key' => $template['key'], 'name' => $template['name'], 'description' => $template['description'],
                'active' => true, 'category' => $template['category'], 'base_template_key' => $template['key'],
                'display_order' => $template['order'], 'created_at' => $now, 'updated_at' => $now,
            ]);
            DB::table('cv_template_versions')->insert([
                'cv_template_id' => $templateId, 'version' => '1.0.0',
                'configuration' => json_encode($template['configuration'], JSON_THROW_ON_ERROR),
                'status' => 'published', 'published_at' => $now, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        $improvements = [
            'cv-01' => ['layout' => 'single-column', 'photo' => 'hidden', 'typography' => 'classic', 'spacing' => 'comfortable', 'header_style' => 'classic', 'section_style' => 'rule', 'accent' => 'neutral', 'page_padding' => 'standard'],
            'cv-02' => ['layout' => 'sidebar-main', 'photo' => 'circle', 'typography' => 'modern', 'spacing' => 'balanced', 'header_style' => 'hero', 'section_style' => 'accent', 'accent' => 'brand', 'page_padding' => 'standard'],
        ];
        foreach ($improvements as $key => $configuration) {
            DB::table('cv_template_versions')->insert([
                'cv_template_id' => DB::table('cv_templates')->where('key', $key)->value('id'),
                'version' => '2.0.0', 'configuration' => json_encode($configuration, JSON_THROW_ON_ERROR),
                'status' => 'published', 'published_at' => $now, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        $removableVersionIds = DB::table('cv_template_versions')->whereIn('id', function ($query) {
            $query->select('cv_template_versions.id')->from('cv_template_versions')
                ->leftJoin('career_cvs', 'career_cvs.cv_template_version_id', '=', 'cv_template_versions.id')
                ->whereNull('career_cvs.id');
        })->where(function ($query) {
            $query->where('version', '2.0.0')->orWhereIn('cv_template_id', DB::table('cv_templates')->whereIn('key', ['cv-03', 'cv-04', 'cv-05'])->pluck('id'));
        })->pluck('id');
        DB::table('cv_template_versions')->whereIn('id', $removableVersionIds)->delete();
        DB::table('cv_templates')->whereIn('key', ['cv-03', 'cv-04', 'cv-05'])->whereNotExists(function ($query) {
            $query->selectRaw('1')->from('cv_template_versions')->whereColumn('cv_template_versions.cv_template_id', 'cv_templates.id');
        })->delete();

        Schema::table('cv_templates', function (Blueprint $table) {
            $table->dropColumn(['base_template_key', 'display_order']);
        });
    }
};
