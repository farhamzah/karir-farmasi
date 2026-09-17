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
        Schema::table('cv_share_links', function (Blueprint $table): void {
            $table->dropForeign(['current_revision_id']);
            $table->foreign('current_revision_id')->references('id')->on('cv_published_revisions')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cv_share_links', function (Blueprint $table): void {
            $table->dropForeign(['current_revision_id']);
            $table->foreign('current_revision_id')->references('id')->on('cv_published_revisions')->restrictOnDelete();
        });
    }
};
