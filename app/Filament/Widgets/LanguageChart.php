<?php

namespace App\Filament\Widgets;

use App\Models\Repository;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class LanguageChart extends ChartWidget
{
    protected static ?string $heading = 'Top Programming Languages';

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $languages = Repository::query()
            ->whereNotNull('language')
            ->select('language', DB::raw('count(*) as count'))
            ->groupBy('language')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Repositories',
                    'data' => $languages->pluck('count')->toArray(),
                    'backgroundColor' => [
                        '#4F46E5',
                        '#10B981',
                        '#F59E0B',
                        '#EF4444',
                        '#8B5CF6',
                        '#EC4899',
                        '#14B8A6',
                        '#F97316',
                        '#06B6D4',
                        '#6366F1',
                    ],
                ],
            ],
            'labels' => $languages->pluck('language')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
            ],
        ];
    }
}
