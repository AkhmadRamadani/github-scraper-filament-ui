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
        Schema::create('repositories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scrape_job_id')->constrained()->onDelete('cascade');
            $table->foreignId('profile_id')->nullable()->constrained()->onDelete('set null');
            $table->string('name')->index();
            $table->text('description')->nullable();
            $table->string('html_url');
            $table->integer('stargazers_count')->default(0)->index();
            $table->integer('forks_count')->default(0)->index();
            $table->integer('watchers_count')->default(0);
            $table->string('language')->nullable()->index();
            $table->integer('open_issues_count')->default(0);
            $table->timestamp('github_created_at')->nullable();
            $table->timestamp('github_updated_at')->nullable();
            $table->integer('size')->default(0);
            $table->string('default_branch')->default('main');
            $table->boolean('fork')->default(false);
            $table->longText('readme_content')->nullable();
            $table->timestamps();

            $table->index(['name', 'created_at']);
            $table->index(['language', 'stargazers_count']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('repositories');
    }
};
