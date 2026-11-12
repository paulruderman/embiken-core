<?php

namespace App\Filament\Shop\Resources\Displays\Schemas;

use App\Support\FeedRegistry;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;

class DisplayForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Text::make(__('app.display_ui.no_feeds'))
                    ->size('sm')
                    ->visible(fn (FeedRegistry $feeds): bool => $feeds->all() === [])
                    ->columnSpanFull(),
                TextInput::make('name')
                    ->label(__('app.display_ui.name'))
                    ->required()
                    ->maxLength(255),
                Select::make('feed_slug')
                    ->label(__('app.display_ui.feed'))
                    ->helperText(__('app.display_ui.feed_help'))
                    ->options(fn (FeedRegistry $feeds): array => collect($feeds->all())
                        ->mapWithKeys(fn (array $feed): array => [$feed['slug'] => $feed['name']])
                        ->all())
                    ->disabled(fn (FeedRegistry $feeds): bool => $feeds->all() === [])
                    ->searchable()
                    ->required(),
                Select::make('standby_feed_slug')
                    ->label(__('app.display_ui.standby_feed'))
                    ->helperText(__('app.display_ui.standby_feed_help'))
                    ->options(fn (FeedRegistry $feeds): array => collect($feeds->all())
                        ->mapWithKeys(fn (array $feed): array => [$feed['slug'] => $feed['name']])
                        ->all())
                    ->searchable()
                    ->nullable(),
                Toggle::make('standby')
                    ->label(__('app.display_ui.standby'))
                    ->helperText(__('app.display_ui.standby_help'))
                    ->default(false),
                Toggle::make('dark_mode')
                    ->label(__('app.display_ui.dark_mode'))
                    ->default(false),
            ]);
    }
}
