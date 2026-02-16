<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProfileResource\Pages;
use App\Models\Profile;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Support\Facades\Storage;
use App\Filament\Resources\ProfileResource\RelationManagers;

class ProfileResource extends Resource
{
    protected static ?string $model = Profile::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    protected static ?string $navigationGroup = 'Data';

    protected static ?int $navigationSort = 2;

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Profile Information')
                    ->schema([
                        Infolists\Components\ImageEntry::make('avatar_url')
                            ->label('Avatar')
                            ->circular()
                            ->defaultImageUrl(fn ($record) => "https://ui-avatars.com/api/?name={$record->login}&size=200"),
                        Infolists\Components\TextEntry::make('display_name')
                            ->label('Name')
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                            ->weight('bold'),
                        Infolists\Components\TextEntry::make('login')
                            ->label('Username')
                            ->url(fn ($record) => $record->html_url, true)
                            ->icon('heroicon-m-link'),
                        Infolists\Components\TextEntry::make('bio')
                            ->columnSpanFull()
                            ->prose(),
                        Infolists\Components\TextEntry::make('cv_file')
                            ->label('CV File')
                            ->url(fn ($record) => Storage::disk('public')->url($record->cv_file))
                            ->openUrlInNewTab()
                            ->visible(fn ($record) => $record->cv_file)
                            ->icon('heroicon-m-document')
                            ->columnSpanFull(),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('company')
                            ->icon('heroicon-m-building-office'),
                        Infolists\Components\TextEntry::make('location')
                            ->icon('heroicon-m-map-pin'),
                        Infolists\Components\TextEntry::make('email')
                            ->icon('heroicon-m-envelope'),
                        Infolists\Components\TextEntry::make('phone')
                            ->icon('heroicon-m-phone')
                            ->visible(fn ($record) => $record->phone),
                        Infolists\Components\TextEntry::make('blog')
                            ->url(fn ($record) => $record->blog, true)
                            ->icon('heroicon-m-globe-alt'),
                        Infolists\Components\TextEntry::make('linkedin_url')
                            ->label('LinkedIn')
                            ->url(fn ($record) => str_starts_with($record->linkedin_url, 'http') ? $record->linkedin_url : 'https://' . $record->linkedin_url, true)
                            ->icon('heroicon-m-link')
                            ->visible(fn ($record) => $record->linkedin_url),
                        Infolists\Components\TextEntry::make('twitter_username')
                            ->label('Twitter')
                            ->url(fn ($record) => "https://twitter.com/{$record->twitter_username}", true)
                            ->icon('heroicon-m-at-symbol'),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Statistics')
                    ->schema([
                        Infolists\Components\TextEntry::make('repositories_count')
                            ->label('Total Repositories')
                            ->state(fn ($record) => $record->repositories()->count())
                            ->numeric()
                            ->icon('heroicon-m-circle-stack'),
                        Infolists\Components\TextEntry::make('public_repos')
                            ->label('Public Repositories')
                            ->numeric()
                            ->icon('heroicon-m-cube'),
                        Infolists\Components\TextEntry::make('public_gists')
                            ->label('Public Gists')
                            ->numeric()
                            ->icon('heroicon-m-document-text'),
                        Infolists\Components\TextEntry::make('followers')
                            ->numeric()
                            ->icon('heroicon-m-users'),
                        Infolists\Components\TextEntry::make('following')
                            ->numeric()
                            ->icon('heroicon-m-user-plus'),
                        Infolists\Components\TextEntry::make('follower_ratio')
                            ->label('Follower Ratio')
                            ->numeric(2),
                        Infolists\Components\TextEntry::make('account_age')
                            ->label('Account Age')
                            ->suffix(' years'),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Dates')
                    ->schema([
                        Infolists\Components\TextEntry::make('github_created_at')
                            ->label('GitHub Account Created')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('github_updated_at')
                            ->label('Last Updated on GitHub')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Scraped At')
                            ->dateTime(),
                    ])
                    ->columns(3)
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('avatar_url')
                    ->label('Avatar')
                    ->circular()
                    ->defaultImageUrl(fn ($record) => "https://ui-avatars.com/api/?name={$record->login}&size=80"),

                Tables\Columns\TextColumn::make('login')
                    ->label('Username')
                    ->searchable()
                    ->sortable()
                    ->url(fn ($record) => $record->html_url, true)
                    ->icon('heroicon-m-link'),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('company')
                    ->searchable()
                    ->toggleable()
                    ->icon('heroicon-m-building-office'),

                Tables\Columns\TextColumn::make('location')
                    ->searchable()
                    ->toggleable()
                    ->icon('heroicon-m-map-pin'),

                Tables\Columns\TextColumn::make('public_repos')
                    ->label('Repos')
                    ->sortable()
                    ->alignCenter()
                    ->numeric(),

                Tables\Columns\TextColumn::make('followers')
                    ->sortable()
                    ->alignCenter()
                    ->numeric(),

                Tables\Columns\TextColumn::make('following')
                    ->sortable()
                    ->alignCenter()
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('follower_ratio')
                    ->label('Ratio')
                    ->sortable()
                    ->alignCenter()
                    ->numeric(2)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Scraped')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('has_company')
                    ->query(fn ($query) => $query->whereNotNull('company'))
                    ->label('Has Company'),

                Tables\Filters\Filter::make('has_location')
                    ->query(fn ($query) => $query->whereNotNull('location'))
                    ->label('Has Location'),

                Tables\Filters\SelectFilter::make('location')
                    ->searchable()
                    ->preload()
                    ->multiple(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Profile Information')
                    ->schema([
                        Forms\Components\TextInput::make('login')
                            ->label('Username')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('name')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('bio')
                            ->columnSpanFull(),
                        Forms\Components\FileUpload::make('cv_file')
                            ->label('CV File')
                            ->disk('public')
                            ->directory('cvs')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Details')
                    ->schema([
                        Forms\Components\TextInput::make('company')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('location')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('blog')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('linkedin_url')
                            ->label('LinkedIn URL')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('twitter_username')
                            ->label('Twitter Username')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('avatar_url')
                            ->label('Avatar URL')
                            ->maxLength(255),
                    ])
                    ->columns(2),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\RepositoriesRelationManager::class,
            RelationManagers\ExperiencesRelationManager::class,
            RelationManagers\EducationsRelationManager::class,
            RelationManagers\ProjectsRelationManager::class,
            RelationManagers\SkillsRelationManager::class,
            RelationManagers\CertificationsRelationManager::class,
            RelationManagers\VolunteeringsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProfiles::route('/'),
            'create' => Pages\CreateProfile::route('/create'),
            'view' => Pages\ViewProfile::route('/{record}'),
            'edit' => Pages\EditProfile::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count() ?: null;
    }
}
