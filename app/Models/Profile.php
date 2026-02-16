<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Profile extends Model
{
    use HasFactory;

    protected $fillable = [
        'scrape_job_id',
        'login',
        'name',
        'bio',
        'company',
        'location',
        'email',
        'blog',
        'twitter_username',
        'public_repos',
        'public_gists',
        'followers',
        'following',
        'github_created_at',
        'github_updated_at',
        'html_url',
        'avatar_url',
        'cv_file',
    ];

    protected $casts = [
        'github_created_at' => 'datetime',
        'github_updated_at' => 'datetime',
    ];

    /**
     * Get the scrape job that owns the profile.
     */
    public function scrapeJob(): BelongsTo
    {
        return $this->belongsTo(ScrapeJob::class);
    }

    /**
     * Get the repositories associated with the profile.
     */
    public function repositories(): HasMany
    {
        return $this->hasMany(Repository::class);
    }

    /**
     * Scope for filtering by login.
     */
    public function scopeLogin($query, string $login)
    {
        return $query->where('login', 'like', "%{$login}%");
    }

    /**
     * Scope for filtering by location.
     */
    public function scopeLocation($query, string $location)
    {
        return $query->where('location', 'like', "%{$location}%");
    }

    /**
     * Scope for filtering by company.
     */
    public function scopeCompany($query, string $company)
    {
        return $query->where('company', 'like', "%{$company}%");
    }

    /**
     * Get follower ratio.
     */
    public function getFollowerRatioAttribute(): float
    {
        if ($this->following === 0) {
            return 0;
        }

        return round($this->followers / $this->following, 2);
    }

    /**
     * Get account age in years.
     */
    public function getAccountAgeAttribute(): ?int
    {
        if (!$this->github_created_at) {
            return null;
        }

        return $this->github_created_at->diffInYears(now());
    }

    /**
     * Get display name.
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->name ?? $this->login;
    }
}
