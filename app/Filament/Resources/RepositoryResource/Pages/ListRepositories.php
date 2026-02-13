<?php

namespace App\Filament\Resources\RepositoryResource\Pages;

use App\Filament\Resources\RepositoryResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListRepositories extends ListRecords
{
    protected static string $resource = RepositoryResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All'),
            
            'popular' => Tab::make('Popular (100+ stars)')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('stargazers_count', '>=', 100))
                ->badge(fn () => $this->getModel()::where('stargazers_count', '>=', 100)->count()),
            
            'active' => Tab::make('Active')
                ->modifyQueryUsing(fn (Builder $query) => 
                    $query->whereRaw('DATEDIFF(NOW(), github_updated_at) <= 30')
                )
                ->badge(fn () => $this->getModel()::whereRaw('DATEDIFF(NOW(), github_updated_at) <= 30')->count())
                ->badgeColor('success'),
            
            'forks' => Tab::make('Forks')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('fork', true))
                ->badge(fn () => $this->getModel()::where('fork', true)->count())
                ->badgeColor('warning'),
        ];
    }
}
