<?php

namespace App\Filament\Resources\ScrapeJobResource\Pages;

use App\Filament\Resources\ScrapeJobResource;
use App\Services\GitHubScraperService;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewScrapeJob extends ViewRecord
{
    protected static string $resource = ScrapeJobResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('sync')
                ->label('Sync Status')
                ->icon('heroicon-o-arrow-path')
                ->color('info')
                ->action(function () {
                    $service = app(GitHubScraperService::class);
                    
                    try {
                        $status = $service->getJobStatus($this->record->job_id);
                        
                        $this->record->update([
                            'status' => $status['status'],
                            'progress' => $status['progress'] ?? 0,
                            'result' => $status['result'] ?? null,
                            'error' => $status['error'] ?? null,
                            'export_files' => $status['export_files'] ?? null,
                        ]);

                        // Save profile and repositories if completed
                        if ($status['status'] === 'completed' && isset($status['result'])) {
                            $this->saveScrapedData($status['result']);
                        }

                        Notification::make()
                            ->title('Job synced successfully')
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Failed to sync job')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->visible(fn () => in_array($this->record->status, ['pending', 'running'])),

            Actions\Action::make('export')
                ->label('Export Data')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->url(fn () => route('scrape-jobs.export', $this->record))
                ->openUrlInNewTab()
                ->visible(fn () => $this->record->status === 'completed'),

            Actions\EditAction::make()
                ->visible(fn () => $this->record->status === 'pending'),
            
            Actions\DeleteAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Job Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('job_id')
                            ->label('Job ID')
                            ->copyable(),
                        Infolists\Components\TextEntry::make('username')
                            ->url(fn () => "https://github.com/{$this->record->username}", true),
                        Infolists\Components\TextEntry::make('status')
                            ->badge()
                            ->color(fn () => $this->record->status_color),
                        Infolists\Components\TextEntry::make('progress')
                            ->formatStateUsing(fn () => $this->record->progress . '%'),
                        Infolists\Components\TextEntry::make('export_format')
                            ->badge(),
                        Infolists\Components\TextEntry::make('created_at')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('started_at')
                            ->dateTime()
                            ->placeholder('Not started'),
                        Infolists\Components\TextEntry::make('completed_at')
                            ->dateTime()
                            ->placeholder('Not completed'),
                        Infolists\Components\TextEntry::make('formatted_duration')
                            ->label('Duration')
                            ->placeholder('N/A'),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Scraping Options')
                    ->schema([
                        Infolists\Components\TextEntry::make('max_repos')
                            ->label('Max Repositories'),
                        Infolists\Components\IconEntry::make('include_readme')
                            ->label('Include README')
                            ->boolean(),
                        Infolists\Components\IconEntry::make('truncate_readme')
                            ->label('Truncate README')
                            ->boolean(),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Statistics')
                    ->schema([
                        Infolists\Components\TextEntry::make('total_repos')
                            ->label('Total Repositories')
                            ->numeric(),
                        Infolists\Components\TextEntry::make('total_stars')
                            ->label('Total Stars')
                            ->numeric(),
                        Infolists\Components\TextEntry::make('total_forks')
                            ->label('Total Forks')
                            ->numeric(),
                    ])
                    ->columns(3)
                    ->visible(fn () => $this->record->status === 'completed'),

                Infolists\Components\Section::make('Error Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('error')
                            ->prose()
                            ->columnSpanFull(),
                    ])
                    ->visible(fn () => $this->record->status === 'failed' && $this->record->error),

                Infolists\Components\Section::make('Export Files')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('export_files')
                            ->schema([
                                Infolists\Components\TextEntry::make('.')
                                    ->label('File'),
                            ])
                            ->contained(false),
                    ])
                    ->visible(fn () => !empty($this->record->export_files)),
            ]);
    }

    protected function saveScrapedData(array $result): void
    {
        // Save profile
        if (isset($result['profile'])) {
            $this->record->profile()->updateOrCreate(
                ['scrape_job_id' => $this->record->id],
                [
                    'login' => $result['profile']['login'],
                    'name' => $result['profile']['name'] ?? null,
                    'bio' => $result['profile']['bio'] ?? null,
                    'company' => $result['profile']['company'] ?? null,
                    'location' => $result['profile']['location'] ?? null,
                    'email' => $result['profile']['email'] ?? null,
                    'blog' => $result['profile']['blog'] ?? null,
                    'twitter_username' => $result['profile']['twitter_username'] ?? null,
                    'public_repos' => $result['profile']['public_repos'] ?? 0,
                    'public_gists' => $result['profile']['public_gists'] ?? 0,
                    'followers' => $result['profile']['followers'] ?? 0,
                    'following' => $result['profile']['following'] ?? 0,
                    'github_created_at' => $result['profile']['created_at'] ?? null,
                    'github_updated_at' => $result['profile']['updated_at'] ?? null,
                    'html_url' => $result['profile']['html_url'],
                    'avatar_url' => $result['profile']['avatar_url'] ?? null,
                ]
            );
        }

        // Save repositories
        if (isset($result['repositories'])) {
            foreach ($result['repositories'] as $repo) {
                $this->record->repositories()->updateOrCreate(
                    [
                        'scrape_job_id' => $this->record->id,
                        'name' => $repo['name'],
                    ],
                    [
                        'description' => $repo['description'] ?? null,
                        'html_url' => $repo['html_url'],
                        'stargazers_count' => $repo['stargazers_count'] ?? 0,
                        'forks_count' => $repo['forks_count'] ?? 0,
                        'watchers_count' => $repo['watchers_count'] ?? 0,
                        'language' => $repo['language'] ?? null,
                        'open_issues_count' => $repo['open_issues_count'] ?? 0,
                        'github_created_at' => $repo['created_at'] ?? null,
                        'github_updated_at' => $repo['updated_at'] ?? null,
                        'size' => $repo['size'] ?? 0,
                        'default_branch' => $repo['default_branch'] ?? 'main',
                        'fork' => $repo['fork'] ?? false,
                        'readme_content' => $repo['readme_content'] ?? null,
                    ]
                );
            }
        }

        // Update job statistics
        $this->record->update([
            'total_repos' => count($result['repositories'] ?? []),
            'total_stars' => $result['total_stars'] ?? 0,
            'total_forks' => $result['total_forks'] ?? 0,
        ]);
    }
}
