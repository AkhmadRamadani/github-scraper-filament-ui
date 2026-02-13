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
        Schema::create('scrape_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('job_id')->unique()->index();
            $table->string('username')->index();
            $table->enum('status', ['pending', 'running', 'completed', 'failed', 'cancelled'])->default('pending')->index();
            $table->integer('progress')->default(0);
            $table->integer('max_repos')->default(100);
            $table->boolean('include_readme')->default(true);
            $table->boolean('truncate_readme')->default(true);
            $table->string('export_format')->default('excel');
            $table->string('github_token')->nullable();
            $table->text('webhook_url')->nullable();
            $table->json('result')->nullable();
            $table->text('error')->nullable();
            $table->json('export_files')->nullable();
            $table->integer('total_stars')->default(0);
            $table->integer('total_forks')->default(0);
            $table->integer('total_repos')->default(0);
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamps();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scrape_jobs');
    }
};
