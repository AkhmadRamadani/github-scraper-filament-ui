<?php

namespace App\Filament\Resources\ScrapeJobResource\Pages;

use App\Filament\Resources\ScrapeJobResource;
use App\Services\GitHubScraperService;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Str;

class CreateScrapeJob extends CreateRecord
{
    protected static string $resource = ScrapeJobResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Generate a unique job ID
        $data['job_id'] = Str::uuid()->toString();
        $data['user_id'] = auth()->id();
        $data['status'] = 'pending';
        $data['progress'] = 0;

        return $data;
    }

    protected function afterCreate(): void
    {
        $service = app(GitHubScraperService::class);

        try {
            // Start async scraping job
            $response = $service->scrapeAsync(
                username: $this->record->username,
                token: $this->record->github_token,
                maxRepos: $this->record->max_repos,
                includeReadme: $this->record->include_readme,
                truncateReadme: $this->record->truncate_readme,
                exportFormat: $this->record->export_format,
                webhookUrl: $this->record->webhook_url
            );

            // Update job with API response
            $this->record->update([
                'job_id' => $response['job_id'],
                'status' => $response['status'],
            ]);

            Notification::make()
                ->title('Scraping job started')
                ->body("Job {$response['job_id']} has been created and is processing.")
                ->success()
                ->send();
        } catch (\Exception $e) {
            $this->record->update([
                'status' => 'failed',
                'error' => $e->getMessage(),
            ]);

            Notification::make()
                ->title('Failed to start scraping job')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }

    protected function getCreatedNotification(): ?Notification
    {
        return null; // We handle notifications in afterCreate
    }
}
