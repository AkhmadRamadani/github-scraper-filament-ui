<?php

namespace App\Http\Controllers;

use App\Models\ScrapeJob;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ScrapeJobExport;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportController extends Controller
{
    /**
     * Export scrape job data
     */
    public function export(ScrapeJob $scrapeJob, Request $request): BinaryFileResponse
    {
        if ($scrapeJob->status !== 'completed') {
            abort(400, 'Job must be completed before exporting');
        }

        $format = $request->get('format', $scrapeJob->export_format);
        $filename = "github-{$scrapeJob->username}-" . now()->format('Y-m-d-His');

        return match ($format) {
            'excel' => $this->exportExcel($scrapeJob, $filename),
            'csv' => $this->exportCsv($scrapeJob, $filename),
            'json' => $this->exportJson($scrapeJob, $filename),
            default => $this->exportExcel($scrapeJob, $filename),
        };
    }

    /**
     * Export as Excel
     */
    protected function exportExcel(ScrapeJob $scrapeJob, string $filename): BinaryFileResponse
    {
        return Excel::download(
            new ScrapeJobExport($scrapeJob),
            "{$filename}.xlsx",
            \Maatwebsite\Excel\Excel::XLSX
        );
    }

    /**
     * Export as CSV
     */
    protected function exportCsv(ScrapeJob $scrapeJob, string $filename): BinaryFileResponse
    {
        return Excel::download(
            new ScrapeJobExport($scrapeJob),
            "{$filename}.csv",
            \Maatwebsite\Excel\Excel::CSV
        );
    }

    /**
     * Export as JSON
     */
    protected function exportJson(ScrapeJob $scrapeJob, string $filename): BinaryFileResponse
    {
        $data = [
            'job' => [
                'job_id' => $scrapeJob->job_id,
                'username' => $scrapeJob->username,
                'status' => $scrapeJob->status,
                'created_at' => $scrapeJob->created_at,
                'completed_at' => $scrapeJob->completed_at,
            ],
            'profile' => $scrapeJob->profile ? [
                'login' => $scrapeJob->profile->login,
                'name' => $scrapeJob->profile->name,
                'bio' => $scrapeJob->profile->bio,
                'company' => $scrapeJob->profile->company,
                'location' => $scrapeJob->profile->location,
                'email' => $scrapeJob->profile->email,
                'blog' => $scrapeJob->profile->blog,
                'twitter_username' => $scrapeJob->profile->twitter_username,
                'public_repos' => $scrapeJob->profile->public_repos,
                'public_gists' => $scrapeJob->profile->public_gists,
                'followers' => $scrapeJob->profile->followers,
                'following' => $scrapeJob->profile->following,
                'html_url' => $scrapeJob->profile->html_url,
                'avatar_url' => $scrapeJob->profile->avatar_url,
            ] : null,
            'repositories' => $scrapeJob->repositories->map(function ($repo) {
                return [
                    'name' => $repo->name,
                    'description' => $repo->description,
                    'html_url' => $repo->html_url,
                    'stars' => $repo->stargazers_count,
                    'forks' => $repo->forks_count,
                    'watchers' => $repo->watchers_count,
                    'language' => $repo->language,
                    'open_issues' => $repo->open_issues_count,
                    'size' => $repo->size,
                    'is_fork' => $repo->fork,
                    'created_at' => $repo->github_created_at,
                    'updated_at' => $repo->github_updated_at,
                ];
            }),
            'statistics' => [
                'total_repos' => $scrapeJob->total_repos,
                'total_stars' => $scrapeJob->total_stars,
                'total_forks' => $scrapeJob->total_forks,
                'top_languages' => $this->getTopLanguages($scrapeJob),
            ],
        ];

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        
        $path = storage_path("app/exports/{$filename}.json");
        file_put_contents($path, $json);

        return response()->download($path)->deleteFileAfterSend();
    }

    /**
     * Get top languages from repositories
     */
    protected function getTopLanguages(ScrapeJob $scrapeJob): array
    {
        return $scrapeJob->repositories()
            ->whereNotNull('language')
            ->select('language', \DB::raw('count(*) as count'))
            ->groupBy('language')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->pluck('count', 'language')
            ->toArray();
    }
}
