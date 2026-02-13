<?php

namespace App\Filament\Resources\RepositoryResource\Pages;

use App\Filament\Resources\RepositoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewRepository extends ViewRecord
{
    protected static string $resource = RepositoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('view_on_github')
                ->label('View on GitHub')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->url(fn () => $this->record->html_url)
                ->openUrlInNewTab()
                ->color('primary'),
        ];
    }
}
