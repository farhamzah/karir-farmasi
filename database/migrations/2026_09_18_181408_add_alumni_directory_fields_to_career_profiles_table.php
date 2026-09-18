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
        Schema::table('career_profiles', function (Blueprint $table) {
            $table->string('alumni_number', 50)->nullable()->unique()->after('talent_reference');
            $table->unsignedSmallInteger('graduation_year')->nullable()->after('alumni_number');
            $table->boolean('visible_in_alumni_directory')->default(true)->after('graduation_year');
            $table->index(['visible_in_alumni_directory', 'alumni_number'], 'career_profiles_alumni_directory_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('career_profiles', function (Blueprint $table) {
            $table->dropIndex('career_profiles_alumni_directory_index');
            $table->dropUnique(['alumni_number']);
            $table->dropColumn(['alumni_number', 'graduation_year', 'visible_in_alumni_directory']);
        });
    }
};
