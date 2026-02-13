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
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scrape_job_id')->constrained()->onDelete('cascade');
            $table->string('login')->index();
            $table->string('name')->nullable();
            $table->text('bio')->nullable();
            $table->string('company')->nullable();
            $table->string('location')->nullable();
            $table->string('email')->nullable();
            $table->string('blog')->nullable();
            $table->string('twitter_username')->nullable();
            $table->integer('public_repos')->default(0);
            $table->integer('public_gists')->default(0);
            $table->integer('followers')->default(0);
            $table->integer('following')->default(0);
            $table->timestamp('github_created_at')->nullable();
            $table->timestamp('github_updated_at')->nullable();
            $table->string('html_url');
            $table->text('avatar_url')->nullable();
            $table->timestamps();

            $table->index(['login', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
