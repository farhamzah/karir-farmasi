<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('career_jobs', function (Blueprint $table) {
            $table->string('flyer_path')->nullable()->after('source_attachment_path');
            $table->string('flyer_alt_text')->nullable()->after('flyer_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('career_jobs', function (Blueprint $table) {
            $table->dropColumn(['flyer_path', 'flyer_alt_text']);
        });
    }
};
