<?php

namespace App\Filament\Resources\ProfileResource\Pages;

use App\Filament\Resources\ProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewProfile extends ViewRecord
{
    protected static string $resource = ProfileResource::class;

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
