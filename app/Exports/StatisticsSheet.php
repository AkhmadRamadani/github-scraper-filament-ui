<?php

namespace App\Exports;

use App\Models\ScrapeJob;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StatisticsSheet implements FromArray, WithTitle, WithStyles, WithColumnWidths
{
    protected ScrapeJob $scrapeJob;

    public function __construct(ScrapeJob $scrapeJob)
    {
        $this->scrapeJob = $scrapeJob;
    }

    /**
     * @return array
     */
    public function array(): array
    {
        $topLanguages = $this->getTopLanguages();
        $reposByLanguage = $this->getRepositoriesByLanguage();

        $data = [
            ['GitHub Scraper Statistics'],
            [''],
            ['Job Information'],
            ['Job ID', $this->scrapeJob->job_id],
            ['Username', $this->scrapeJob->username],
            ['Status', ucfirst($this->scrapeJob->status)],
            ['Created At', $this->scrapeJob->created_at->format('Y-m-d H:i:s')],
            ['Completed At', $this->scrapeJob->completed_at?->format('Y-m-d H:i:s') ?? 'N/A'],
            ['Duration', $this->scrapeJob->formatted_duration ?? 'N/A'],
            [''],
            ['Repository Statistics'],
            ['Total Repositories', $this->scrapeJob->total_repos],
            ['Total Stars', $this->scrapeJob->total_stars],
            ['Total Forks', $this->scrapeJob->total_forks],
            ['Average Stars per Repo', $this->scrapeJob->total_repos > 0 ? round($this->scrapeJob->total_stars / $this->scrapeJob->total_repos, 2) : 0],
            ['Average Forks per Repo', $this->scrapeJob->total_repos > 0 ? round($this->scrapeJob->total_forks / $this->scrapeJob->total_repos, 2) : 0],
            [''],
            ['Top Languages', 'Repository Count'],
        ];

        foreach ($topLanguages as $language => $count) {
            $data[] = [$language, $count];
        }

        $data[] = [''];
        $data[] = ['Language Distribution'];
        foreach ($reposByLanguage as $language => $count) {
            $percentage = $this->scrapeJob->total_repos > 0
                ? round(($count / $this->scrapeJob->total_repos) * 100, 2)
                : 0;
            $data[] = [$language, $count, $percentage . '%'];
        }

        return $data;
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return 'Statistics';
    }

    /**
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 16],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4F46E5']
                ],
                'font' => ['color' => ['rgb' => 'FFFFFF'], 'bold' => true],
            ],
            3 => ['font' => ['bold' => true, 'size' => 12]],
            11 => ['font' => ['bold' => true, 'size' => 12]],
            18 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E5E7EB']
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public function columnWidths(): array
    {
        return [
            'A' => 30,
            'B' => 25,
            'C' => 15,
        ];
    }

    /**
     * Get top languages
     */
    protected function getTopLanguages(): array
    {
        return $this->scrapeJob->repositories()
            ->whereNotNull('language')
            ->select('language', DB::raw('count(*) as count'))
            ->groupBy('language')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->pluck('count', 'language')
            ->toArray();
    }

    /**
     * Get all repositories by language
     */
    protected function getRepositoriesByLanguage(): array
    {
        return $this->scrapeJob->repositories()
            ->whereNotNull('language')
            ->select('language', DB::raw('count(*) as count'))
            ->groupBy('language')
            ->orderBy('count', 'desc')
            ->pluck('count', 'language')
            ->toArray();
    }
}
