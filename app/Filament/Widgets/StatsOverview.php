<?php

namespace App\Filament\Widgets;

use App\Models\ScrapeJob;
use App\Models\Profile;
use App\Models\Repository;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $totalJobs = ScrapeJob::count();
        $completedJobs = ScrapeJob::where('status', 'completed')->count();
        $runningJobs = ScrapeJob::where('status', 'running')->count();
        $failedJobs = ScrapeJob::where('status', 'failed')->count();
        $totalProfiles = Profile::count();
        $totalRepos = Repository::count();
        $totalStars = ScrapeJob::sum('total_stars');

        return [
            Stat::make('Total Jobs', $totalJobs)
                ->description('All scraping jobs')
                ->descriptionIcon('heroicon-m-queue-list')
                ->color('primary')
                ->chart([7, 12, 9, 14, 18, 15, $totalJobs]),

            Stat::make('Completed Jobs', $completedJobs)
                ->description($totalJobs > 0 ? round(($completedJobs / $totalJobs) * 100, 1) . '% success rate' : 'N/A')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success')
                ->chart([5, 8, 12, 14, 16, 18, $completedJobs]),

            Stat::make('Running Jobs', $runningJobs)
                ->description('Currently processing')
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color('info'),

            Stat::make('Failed Jobs', $failedJobs)
                ->description($totalJobs > 0 ? round(($failedJobs / $totalJobs) * 100, 1) . '% failure rate' : 'N/A')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),

            Stat::make('Profiles Scraped', $totalProfiles)
                ->description('Unique GitHub profiles')
                ->descriptionIcon('heroicon-m-user-circle')
                ->color('warning'),

            Stat::make('Repositories', number_format($totalRepos))
                ->description('Total repositories scraped')
                ->descriptionIcon('heroicon-m-cube')
                ->color('success'),

            Stat::make('Total Stars', number_format($totalStars))
                ->description('Across all repositories')
                ->descriptionIcon('heroicon-m-star')
                ->color('warning'),
        ];
    }
}
