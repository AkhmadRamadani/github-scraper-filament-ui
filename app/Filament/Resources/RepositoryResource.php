<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RepositoryResource\Pages;
use App\Models\Repository;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class RepositoryResource extends Resource
{
    protected static ?string $model = Repository::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationGroup = 'Data';

    protected static ?int $navigationSort = 3;

    protected static ?string $pluralLabel = 'Repositories';

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Repository Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('name')
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                            ->weight('bold'),
                        Infolists\Components\TextEntry::make('html_url')
                            ->label('URL')
                            ->url(fn ($record) => $record->html_url, true)
                            ->icon('heroicon-m-link'),
                        Infolists\Components\TextEntry::make('description')
                            ->columnSpanFull()
                            ->prose(),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Statistics')
                    ->schema([
                        Infolists\Components\TextEntry::make('stargazers_count')
                            ->label('Stars')
                            ->numeric()
                            ->icon('heroicon-m-star'),
                        Infolists\Components\TextEntry::make('forks_count')
                            ->label('Forks')
                            ->numeric()
                            ->icon('heroicon-m-code-bracket'),
                        Infolists\Components\TextEntry::make('watchers_count')
                            ->label('Watchers')
                            ->numeric()
                            ->icon('heroicon-m-eye'),
                        Infolists\Components\TextEntry::make('total_engagement')
                            ->label('Total Engagement')
                            ->numeric(),
                        Infolists\Components\TextEntry::make('open_issues_count')
                            ->label('Open Issues')
                            ->numeric()
                            ->icon('heroicon-m-exclamation-circle'),
                        Infolists\Components\TextEntry::make('size_mb')
                            ->label('Size')
                            ->suffix(' MB'),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('language')
                            ->badge()
                            ->color('primary'),
                        Infolists\Components\TextEntry::make('default_branch')
                            ->badge(),
                        Infolists\Components\IconEntry::make('fork')
                            ->label('Is Fork')
                            ->boolean(),
                        Infolists\Components\TextEntry::make('github_created_at')
                            ->label('Created')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('github_updated_at')
                            ->label('Last Updated')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('days_since_update')
                            ->label('Days Since Update')
                            ->suffix(' days'),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('README Content')
                    ->schema([
                        Infolists\Components\TextEntry::make('readme_content')
                            ->prose()
                            ->columnSpanFull()
                            ->markdown(),
                    ])
                    ->visible(fn ($record) => $record->hasReadme())
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->url(fn ($record) => $record->html_url, true)
                    ->icon('heroicon-m-link')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('description')
                    ->limit(50)
                    ->searchable()
                    ->toggleable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('language')
                    ->badge()
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('stargazers_count')
                    ->label('⭐ Stars')
                    ->sortable()
                    ->alignCenter()
                    ->numeric(),

                Tables\Columns\TextColumn::make('forks_count')
                    ->label('🍴 Forks')
                    ->sortable()
                    ->alignCenter()
                    ->numeric(),

                Tables\Columns\TextColumn::make('watchers_count')
                    ->label('👁 Watchers')
                    ->sortable()
                    ->alignCenter()
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('total_engagement')
                    ->label('Engagement')
                    ->sortable()
                    ->alignCenter()
                    ->numeric()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('fork')
                    ->label('Fork')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('days_since_update')
                    ->label('Last Update')
                    ->suffix(' days ago')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Scraped')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('language')
                    ->searchable()
                    ->preload()
                    ->multiple(),

                Tables\Filters\Filter::make('fork')
                    ->label('Show Forks')
                    ->query(fn ($query) => $query->where('fork', true)),

                Tables\Filters\Filter::make('active')
                    ->label('Active (Updated in last 30 days)')
                    ->query(fn ($query) => $query->whereRaw('DATEDIFF(NOW(), github_updated_at) <= 30')),

                Tables\Filters\Filter::make('stars')
                    ->form([
                        \Filament\Forms\Components\TextInput::make('min_stars')
                            ->numeric()
                            ->label('Minimum Stars'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query->when(
                            $data['min_stars'],
                            fn ($query) => $query->where('stargazers_count', '>=', $data['min_stars'])
                        );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('view_on_github')
                    ->label('GitHub')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn ($record) => $record->html_url)
                    ->openUrlInNewTab()
                    ->color('primary'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('stargazers_count', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRepositories::route('/'),
            'view' => Pages\ViewRepository::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count() ?: null;
    }
}
