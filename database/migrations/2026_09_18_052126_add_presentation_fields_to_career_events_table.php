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
        Schema::table('career_events', function (Blueprint $table) {
            $table->string('flyer_path')->nullable()->after('description');
            $table->string('flyer_mime', 100)->nullable()->after('flyer_path');
            $table->string('flyer_alt_text')->nullable()->after('flyer_mime');
            $table->text('registration_notes')->nullable()->after('registration_closes_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('career_events', function (Blueprint $table) {
            $table->dropColumn(['flyer_path', 'flyer_mime', 'flyer_alt_text', 'registration_notes']);
        });
    }
};
