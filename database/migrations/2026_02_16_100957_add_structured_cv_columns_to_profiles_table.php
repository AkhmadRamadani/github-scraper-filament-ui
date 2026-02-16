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
        Schema::table('profiles', function (Blueprint $table) {
            $table->json('technical_skills')->nullable();
            $table->json('work_experience')->nullable();
            $table->json('education')->nullable();
            $table->json('projects')->nullable();
            $table->json('certifications')->nullable();
            $table->json('volunteering')->nullable();
            $table->string('phone')->nullable();
            $table->string('linkedin_url')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn([
                'technical_skills',
                'work_experience',
                'education',
                'projects',
                'certifications',
                'volunteering',
                'phone',
                'linkedin_url',
            ]);
        });
    }
};
