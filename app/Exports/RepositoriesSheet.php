<?php

namespace App\Exports;

use App\Models\ScrapeJob;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RepositoriesSheet implements FromCollection, WithTitle, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    protected ScrapeJob $scrapeJob;

    public function __construct(ScrapeJob $scrapeJob)
    {
        $this->scrapeJob = $scrapeJob;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->scrapeJob->repositories()
            ->orderBy('stargazers_count', 'desc')
            ->get();
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return 'Repositories';
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'Name',
            'Description',
            'URL',
            'Language',
            'Stars',
            'Forks',
            'Watchers',
            'Open Issues',
            'Size (KB)',
            'Is Fork',
            'Default Branch',
            'Created At',
            'Updated At',
            'Days Since Update',
        ];
    }

    /**
     * @param $repository
     * @return array
     */
    public function map($repository): array
    {
        return [
            $repository->name,
            $repository->description,
            $repository->html_url,
            $repository->language,
            $repository->stargazers_count,
            $repository->forks_count,
            $repository->watchers_count,
            $repository->open_issues_count,
            $repository->size,
            $repository->fork ? 'Yes' : 'No',
            $repository->default_branch,
            $repository->github_created_at?->format('Y-m-d H:i:s'),
            $repository->github_updated_at?->format('Y-m-d H:i:s'),
            $repository->days_since_update,
        ];
    }

    /**
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4F46E5']
                ],
                'font' => ['color' => ['rgb' => 'FFFFFF'], 'bold' => true],
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
            'B' => 60,
            'C' => 50,
            'D' => 15,
            'E' => 12,
            'F' => 12,
            'G' => 12,
            'H' => 15,
            'I' => 12,
            'J' => 12,
            'K' => 18,
            'L' => 20,
            'M' => 20,
            'N' => 18,
        ];
    }
}
