<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Repository extends Model
{
    use HasFactory;

    protected $fillable = [
        'scrape_job_id',
        'profile_id',
        'name',
        'description',
        'html_url',
        'stargazers_count',
        'forks_count',
        'watchers_count',
        'language',
        'open_issues_count',
        'github_created_at',
        'github_updated_at',
        'size',
        'default_branch',
        'fork',
        'readme_content',
    ];

    protected $casts = [
        'fork' => 'boolean',
        'github_created_at' => 'datetime',
        'github_updated_at' => 'datetime',
    ];

    /**
     * Get the scrape job that owns the repository.
     */
    public function scrapeJob(): BelongsTo
    {
        return $this->belongsTo(ScrapeJob::class);
    }

    /**
     * Get the profile that owns the repository.
     */
    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    /**
     * Scope for filtering by language.
     */
    public function scopeLanguage($query, string $language)
    {
        return $query->where('language', $language);
    }

    /**
     * Scope for filtering by name.
     */
    public function scopeName($query, string $name)
    {
        return $query->where('name', 'like', "%{$name}%");
    }

    /**
     * Scope for filtering forked repositories.
     */
    public function scopeForked($query, bool $forked = true)
    {
        return $query->where('fork', $forked);
    }

    /**
     * Scope for ordering by stars.
     */
    public function scopePopular($query)
    {
        return $query->orderBy('stargazers_count', 'desc');
    }

    /**
     * Scope for filtering repositories with minimum stars.
     */
    public function scopeMinStars($query, int $stars)
    {
        return $query->where('stargazers_count', '>=', $stars);
    }

    /**
     * Get total engagement (stars + forks + watchers).
     */
    public function getTotalEngagementAttribute(): int
    {
        return $this->stargazers_count + $this->forks_count + $this->watchers_count;
    }

    /**
     * Get size in MB.
     */
    public function getSizeMbAttribute(): float
    {
        return round($this->size / 1024, 2);
    }

    /**
     * Check if repository has README.
     */
    public function hasReadme(): bool
    {
        return !empty($this->readme_content);
    }

    /**
     * Get truncated description.
     */
    public function getTruncatedDescriptionAttribute(): ?string
    {
        if (!$this->description) {
            return null;
        }

        return strlen($this->description) > 100
            ? substr($this->description, 0, 100) . '...'
            : $this->description;
    }

    /**
     * Get repository age in days.
     */
    public function getAgeInDaysAttribute(): ?int
    {
        if (!$this->github_created_at) {
            return null;
        }

        return $this->github_created_at->diffInDays(now());
    }

    /**
     * Get days since last update.
     */
    public function getDaysSinceUpdateAttribute(): ?int
    {
        if (!$this->github_updated_at) {
            return null;
        }

        return $this->github_updated_at->diffInDays(now());
    }

    /**
     * Check if repository is active (updated in last 30 days).
     */
    public function isActive(): bool
    {
        return $this->days_since_update !== null && $this->days_since_update <= 30;
    }
}
