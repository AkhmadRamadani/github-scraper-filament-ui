<?php

namespace App\Filament\Resources\ScrapeJobResource\Pages;

use App\Filament\Resources\ScrapeJobResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditScrapeJob extends EditRecord
{
    protected static string $resource = ScrapeJobResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
