<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ScrapeJobResource\Pages;
use App\Models\ScrapeJob;
use App\Services\GitHubScraperService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class ScrapeJobResource extends Resource
{
    protected static ?string $model = ScrapeJob::class;

    protected static ?string $navigationIcon = 'heroicon-o-cloud-arrow-down';

    protected static ?string $navigationGroup = 'Scraping';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Job Information')
                    ->schema([
                        Forms\Components\TextInput::make('username')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Enter GitHub username')
                            ->helperText('The GitHub username to scrape'),

                        Forms\Components\TextInput::make('github_token')
                            ->label('GitHub Token (Optional)')
                            ->password()
                            ->maxLength(255)
                            ->helperText('For higher rate limits'),

                        Forms\Components\Select::make('export_format')
                            ->options([
                                'excel' => 'Excel (.xlsx)',
                                'csv' => 'CSV',
                                'json' => 'JSON',
                            ])
                            ->default('excel')
                            ->required(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Scraping Options')
                    ->schema([
                        Forms\Components\TextInput::make('max_repos')
                            ->label('Maximum Repositories')
                            ->numeric()
                            ->default(100)
                            ->minValue(1)
                            ->maxValue(500)
                            ->required()
                            ->helperText('Maximum number of repositories to fetch (1-500)'),

                        Forms\Components\Toggle::make('include_readme')
                            ->label('Include README Content')
                            ->default(true)
                            ->inline(false)
                            ->helperText('Fetch README content for each repository'),

                        Forms\Components\Toggle::make('truncate_readme')
                            ->label('Truncate README')
                            ->default(true)
                            ->inline(false)
                            ->helperText('Truncate README content to 1000 characters'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Advanced Options')
                    ->schema([
                        Forms\Components\TextInput::make('webhook_url')
                            ->label('Webhook URL (Optional)')
                            ->url()
                            ->maxLength(2083)
                            ->placeholder('https://example.com/webhook')
                            ->helperText('Receive notification when job completes'),
                    ])
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('job_id')
                    ->label('Job ID')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Job ID copied')
                    ->tooltip('Click to copy')
                    ->limit(8),

                Tables\Columns\TextColumn::make('username')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-m-user')
                    ->url(fn (ScrapeJob $record) => "https://github.com/{$record->username}", true),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'gray' => 'pending',
                        'info' => 'running',
                        'success' => 'completed',
                        'danger' => 'failed',
                        'warning' => 'cancelled',
                    ])
                    ->icons([
                        'heroicon-o-clock' => 'pending',
                        'heroicon-o-arrow-path' => 'running',
                        'heroicon-o-check-circle' => 'completed',
                        'heroicon-o-x-circle' => 'failed',
                        'heroicon-o-no-symbol' => 'cancelled',
                    ]),

                Tables\Columns\TextColumn::make('progress')
                    ->label('Progress')
                    ->formatStateUsing(fn (int $state) => $state . '%')
                    ->badge()
                    ->color(fn (int $state) => match (true) {
                        $state < 30 => 'danger',
                        $state < 70 => 'warning',
                        default => 'success',
                    }),

                Tables\Columns\TextColumn::make('total_repos')
                    ->label('Repos')
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('total_stars')
                    ->label('⭐ Stars')
                    ->sortable()
                    ->alignCenter()
                    ->numeric(),

                Tables\Columns\TextColumn::make('total_forks')
                    ->label('🍴 Forks')
                    ->sortable()
                    ->alignCenter()
                    ->numeric(),

                Tables\Columns\TextColumn::make('export_format')
                    ->badge()
                    ->colors([
                        'success' => 'excel',
                        'info' => 'csv',
                        'warning' => 'json',
                    ]),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('formatted_duration')
                    ->label('Duration')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'running' => 'Running',
                        'completed' => 'Completed',
                        'failed' => 'Failed',
                        'cancelled' => 'Cancelled',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('export_format')
                    ->options([
                        'excel' => 'Excel',
                        'csv' => 'CSV',
                        'json' => 'JSON',
                    ]),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from'),
                        Forms\Components\DatePicker::make('created_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('sync')
                    ->label('Sync Status')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->action(function (ScrapeJob $record) {
                        $service = app(GitHubScraperService::class);
                        
                        try {
                            $status = $service->getJobStatus($record->job_id);
                            
                            $record->update([
                                'status' => $status['status'],
                                'progress' => $status['progress'] ?? 0,
                                'result' => $status['result'] ?? null,
                                'error' => $status['error'] ?? null,
                                'export_files' => $status['export_files'] ?? null,
                            ]);

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
                    ->visible(fn (ScrapeJob $record) => in_array($record->status, ['pending', 'running'])),

                Tables\Actions\Action::make('export')
                    ->label('Export')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->url(fn (ScrapeJob $record) => route('scrape-jobs.export', $record))
                    ->openUrlInNewTab()
                    ->visible(fn (ScrapeJob $record) => $record->status === 'completed'),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn (ScrapeJob $record) => $record->status === 'pending'),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('10s');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListScrapeJobs::route('/'),
            'create' => Pages\CreateScrapeJob::route('/create'),
            'view' => Pages\ViewScrapeJob::route('/{record}'),
            'edit' => Pages\EditScrapeJob::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'running')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'info';
    }
}
