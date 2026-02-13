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
                            $this->record->saveScrapedData($status['result']);
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

}
