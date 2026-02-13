<?php

namespace App\Filament\Widgets;

use App\Models\ScrapeJob;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentJobs extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 2;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ScrapeJob::query()
                    ->latest()
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('username')
                    ->searchable()
                    ->url(fn (ScrapeJob $record) => "https://github.com/{$record->username}", true),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'gray' => 'pending',
                        'info' => 'running',
                        'success' => 'completed',
                        'danger' => 'failed',
                        'warning' => 'cancelled',
                    ]),

                Tables\Columns\TextColumn::make('progress')
                    ->formatStateUsing(fn (int $state) => $state . '%'),

                Tables\Columns\TextColumn::make('total_repos')
                    ->label('Repos'),

                Tables\Columns\TextColumn::make('total_stars')
                    ->label('Stars')
                    ->numeric(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->since(),
            ])
            ->heading('Recent Scraping Jobs')
            ->poll('10s');
    }
}
