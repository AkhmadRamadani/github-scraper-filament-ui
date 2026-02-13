<?php

namespace App\Exports;

use App\Models\ScrapeJob;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ScrapeJobExport implements WithMultipleSheets
{
    protected ScrapeJob $scrapeJob;

    public function __construct(ScrapeJob $scrapeJob)
    {
        $this->scrapeJob = $scrapeJob;
    }

    /**
     * @return array
     */
    public function sheets(): array
    {
        $sheets = [
            new ProfileSheet($this->scrapeJob),
            new RepositoriesSheet($this->scrapeJob),
            new StatisticsSheet($this->scrapeJob),
        ];

        return $sheets;
    }
}
