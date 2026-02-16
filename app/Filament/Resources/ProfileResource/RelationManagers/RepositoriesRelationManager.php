<?php

namespace App\Filament\Resources\ProfileResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RepositoriesRelationManager extends RelationManager
{
    protected static string $relationship = 'repositories';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('html_url')
                    ->label('HTML URL')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->maxLength(65535)
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('language')
                    ->maxLength(255),
                Forms\Components\TextInput::make('stargazers_count')
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('forks_count')
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('watchers_count')
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('open_issues_count')
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('size')
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('default_branch')
                    ->maxLength(255)
                    ->default('main'),
                Forms\Components\Toggle::make('fork')
                    ->required(),
                Forms\Components\Textarea::make('readme_content')
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('description')
                    ->limit(50),
                Tables\Columns\TextColumn::make('language'),
                Tables\Columns\TextColumn::make('stargazers_count')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('forks_count')
                    ->numeric()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
