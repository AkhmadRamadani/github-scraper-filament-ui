<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ScrapeJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_id',
        'username',
        'status',
        'progress',
        'max_repos',
        'include_readme',
        'truncate_readme',
        'export_format',
        'github_token',
        'webhook_url',
        'result',
        'error',
        'export_files',
        'total_stars',
        'total_forks',
        'total_repos',
        'user_id',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'include_readme' => 'boolean',
        'truncate_readme' => 'boolean',
        'result' => 'array',
        'export_files' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'pending',
        'progress' => 0,
        'max_repos' => 100,
        'include_readme' => true,
        'truncate_readme' => true,
        'export_format' => 'excel',
    ];

    /**
     * Get the user that owns the job.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the profile associated with the job.
     */
    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }

    /**
     * Get the repositories associated with the job.
     */
    public function repositories(): HasMany
    {
        return $this->hasMany(Repository::class);
    }

    /**
     * Scope for filtering by status.
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for filtering by username.
     */
    public function scopeUsername($query, string $username)
    {
        return $query->where('username', 'like', "%{$username}%");
    }

    /**
     * Get status badge color.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'gray',
            'running' => 'info',
            'completed' => 'success',
            'failed' => 'danger',
            'cancelled' => 'warning',
            default => 'gray',
        };
    }

    /**
     * Get status icon.
     */
    public function getStatusIconAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'heroicon-o-clock',
            'running' => 'heroicon-o-arrow-path',
            'completed' => 'heroicon-o-check-circle',
            'failed' => 'heroicon-o-x-circle',
            'cancelled' => 'heroicon-o-no-symbol',
            default => 'heroicon-o-question-mark-circle',
        };
    }

    /**
     * Check if job is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if job is running.
     */
    public function isRunning(): bool
    {
        return $this->status === 'running';
    }

    /**
     * Check if job is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if job has failed.
     */
    public function hasFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Check if job was cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Get the duration of the job.
     */
    public function getDurationAttribute(): ?int
    {
        if (!$this->started_at || !$this->completed_at) {
            return null;
        }

        return $this->started_at->diffInSeconds($this->completed_at);
    }

    /**
     * Get formatted duration.
     */
    public function getFormattedDurationAttribute(): ?string
    {
        if (!$this->duration) {
            return null;
        }

        $minutes = floor($this->duration / 60);
        $seconds = $this->duration % 60;

        if ($minutes > 0) {
            return "{$minutes}m {$seconds}s";
        }

        return "{$seconds}s";
    }
}
