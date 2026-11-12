<?php

namespace App\Filament\Platform\Resources\MarketingPages\Schemas;

use App\Models\MarketingPage;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class MarketingPageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('page_type')
                    ->label('Content type')
                    ->options(['page' => 'Page', 'article' => 'Journal article'])
                    ->required()
                    ->default('page'),
                TextInput::make('title')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (?string $state, callable $set, callable $get): void {
                        if (blank($get('slug')) && filled($state)) {
                            $set('slug', Str::slug($state));
                        }
                    }),
                TextInput::make('slug')
                    ->required()
                    ->maxLength(180)
                    ->alphaDash()
                    ->unique(MarketingPage::class, 'slug', ignoreRecord: true)
                    ->helperText('Pages use /{slug}; journal articles use /blog/{slug}. Keep existing published slugs stable.'),
                Textarea::make('excerpt')
                    ->rows(3)
                    ->maxLength(500)
                    ->columnSpanFull()
                    ->helperText('Shown as the page introduction and used as a search description fallback.'),
                RichEditor::make('content_html')
                    ->label('Page content')
                    ->required()
                    ->toolbarButtons([
                        ['bold', 'italic', 'link'],
                        ['h2', 'h3'],
                        ['bulletList', 'orderedList', 'blockquote'],
                        ['undo', 'redo'],
                    ])
                    ->columnSpanFull()
                    ->helperText('Formatting is sanitized before public display.'),
                TextInput::make('seo_title')
                    ->maxLength(255)
                    ->helperText('Optional browser and search result title; defaults to the page title.'),
                Textarea::make('seo_description')
                    ->rows(3)
                    ->maxLength(320)
                    ->helperText('Optional search and social description; defaults to the page introduction.'),
                TextInput::make('sort_order')
                    ->numeric()
                    ->integer()
                    ->default(0)
                    ->required(),
                DateTimePicker::make('published_at')
                    ->native(false)
                    ->seconds(false)
                    ->displayFormat(__('app.datetime_ui.input_format'))
                    ->helperText('Leave blank to keep this as a draft. Future dates publish automatically when reached.'),
            ]);
    }
}
