<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class GitHubScraperService
{
    protected string $baseUrl;
    protected int $timeout;
    protected int $retryTimes;
    protected int $retryDelay;

    public function __construct()
    {
        $this->baseUrl = config('services.github_scraper.api_url');
        $this->timeout = config('services.github_scraper.timeout');
        $this->retryTimes = config('services.github_scraper.retry_times');
        $this->retryDelay = config('services.github_scraper.retry_delay');
    }

    /**
     * Get HTTP client with retry logic.
     */
    protected function getClient()
    {
        return Http::timeout($this->timeout)
            ->retry($this->retryTimes, $this->retryDelay)
            ->acceptJson();
    }

    /**
     * Scrape GitHub user profile.
     */
    public function scrapeProfile(string $username, ?string $token = null, bool $useCache = true): array
    {
        try {
            $response = $this->getClient()->get("{$this->baseUrl}/api/v1/scrape/profile/{$username}", [
                'token' => $token,
                'use_cache' => $useCache,
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            throw new \Exception("Failed to scrape profile: " . $response->body());
        } catch (\Exception $e) {
            Log::error("Profile scraping failed for {$username}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Scrape GitHub user repositories.
     */
    public function scrapeRepositories(
        string $username,
        ?string $token = null,
        int $maxRepos = 100,
        bool $includeReadme = true,
        bool $useCache = true
    ): array {
        try {
            $response = $this->getClient()->get("{$this->baseUrl}/api/v1/scrape/repositories/{$username}", [
                'token' => $token,
                'max_repos' => $maxRepos,
                'include_readme' => $includeReadme,
                'use_cache' => $useCache,
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            throw new \Exception("Failed to scrape repositories: " . $response->body());
        } catch (\Exception $e) {
            Log::error("Repository scraping failed for {$username}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Complete scrape of user profile and repositories.
     */
    public function scrapeComplete(
        string $username,
        ?string $token = null,
        int $maxRepos = 100,
        bool $includeReadme = true,
        bool $truncateReadme = true,
        bool $useCache = true
    ): array {
        try {
            $response = $this->getClient()->get("{$this->baseUrl}/api/v1/scrape/complete/{$username}", [
                'token' => $token,
                'max_repos' => $maxRepos,
                'include_readme' => $includeReadme,
                'truncate_readme' => $truncateReadme,
                'use_cache' => $useCache,
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            throw new \Exception("Failed to complete scrape: " . $response->body());
        } catch (\Exception $e) {
            Log::error("Complete scraping failed for {$username}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Start async scraping job.
     */
    public function scrapeAsync(
        string $username,
        ?string $token = null,
        int $maxRepos = 100,
        bool $includeReadme = true,
        bool $truncateReadme = true,
        string $exportFormat = 'excel',
        ?string $webhookUrl = null
    ): array {
        try {
            $response = $this->getClient()->post("{$this->baseUrl}/api/v1/scrape/async/{$username}", [
                'username' => $username,
                'token' => $token,
                'max_repos' => $maxRepos,
                'include_readme' => $includeReadme,
                'truncate_readme' => $truncateReadme,
                'export_format' => $exportFormat,
                'webhook_url' => $webhookUrl,
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            throw new \Exception("Failed to start async scrape: " . $response->body());
        } catch (\Exception $e) {
            Log::error("Async scraping failed for {$username}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get job status.
     */
    public function getJobStatus(string $jobId): array
    {
        try {
            $response = $this->getClient()->get("{$this->baseUrl}/api/v1/jobs/{$jobId}");

            if ($response->successful()) {
                return $response->json();
            }

            throw new \Exception("Failed to get job status: " . $response->body());
        } catch (\Exception $e) {
            Log::error("Getting job status failed for {$jobId}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * List all jobs.
     */
    public function listJobs(?string $status = null, int $limit = 100): array
    {
        try {
            $response = $this->getClient()->get("{$this->baseUrl}/api/v1/jobs", [
                'status' => $status,
                'limit' => $limit,
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            throw new \Exception("Failed to list jobs: " . $response->body());
        } catch (\Exception $e) {
            Log::error("Listing jobs failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Cancel a running job.
     */
    public function cancelJob(string $jobId): array
    {
        try {
            $response = $this->getClient()->post("{$this->baseUrl}/api/v1/jobs/{$jobId}/cancel");

            if ($response->successful()) {
                return $response->json();
            }

            throw new \Exception("Failed to cancel job: " . $response->body());
        } catch (\Exception $e) {
            Log::error("Cancelling job failed for {$jobId}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Delete a job.
     */
    public function deleteJob(string $jobId): array
    {
        try {
            $response = $this->getClient()->delete("{$this->baseUrl}/api/v1/jobs/{$jobId}");

            if ($response->successful()) {
                return $response->json();
            }

            throw new \Exception("Failed to delete job: " . $response->body());
        } catch (\Exception $e) {
            Log::error("Deleting job failed for {$jobId}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Export job data.
     */
    public function exportJobData(string $jobId, string $format = 'excel', bool $download = false): array
    {
        try {
            $response = $this->getClient()->get("{$this->baseUrl}/api/v1/export/{$jobId}/{$format}", [
                'download' => $download,
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            throw new \Exception("Failed to export job data: " . $response->body());
        } catch (\Exception $e) {
            Log::error("Exporting job data failed for {$jobId}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Download exported file.
     */
    public function downloadFile(string $jobId, string $filename): string
    {
        try {
            $response = $this->getClient()->get("{$this->baseUrl}/api/v1/download/{$jobId}/{$filename}");

            if ($response->successful()) {
                return $response->body();
            }

            throw new \Exception("Failed to download file: " . $response->status());
        } catch (\Exception $e) {
            Log::error("Downloading file failed for {$jobId}/{$filename}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * List export files for a job.
     */
    public function listExportFiles(string $jobId): array
    {
        try {
            $response = $this->getClient()->get("{$this->baseUrl}/api/v1/export/{$jobId}/files");

            if ($response->successful()) {
                return $response->json();
            }

            throw new \Exception("Failed to list export files: " . $response->body());
        } catch (\Exception $e) {
            Log::error("Listing export files failed for {$jobId}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get job statistics.
     */
    public function getJobStats(): array
    {
        try {
            $response = $this->getClient()->get("{$this->baseUrl}/api/v1/jobs/stats");

            if ($response->successful()) {
                return $response->json();
            }

            throw new \Exception("Failed to get job stats: " . $response->body());
        } catch (\Exception $e) {
            Log::error("Getting job stats failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Health check.
     */
    public function healthCheck(): array
    {
        try {
            $response = $this->getClient()->get("{$this->baseUrl}/health");

            if ($response->successful()) {
                return $response->json();
            }

            throw new \Exception("Health check failed: " . $response->body());
        } catch (\Exception $e) {
            Log::error("Health check failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get API statistics.
     */
    public function getApiStats(): array
    {
        try {
            $response = $this->getClient()->get("{$this->baseUrl}/api/v1/stats");

            if ($response->successful()) {
                return $response->json();
            }

            throw new \Exception("Failed to get API stats: " . $response->body());
        } catch (\Exception $e) {
            Log::error("Getting API stats failed: " . $e->getMessage());
            throw $e;
        }
    }
}
