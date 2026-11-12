<?php

namespace App\Filament\Forms\Components\RichEditor\RichContentCustomBlocks;

use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

class ShopCalloutBlock extends RichContentCustomBlock
{
    public static function getId(): string
    {
        return 'shop_callout';
    }

    public static function getLabel(): string
    {
        return 'Shop callout';
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalDescription(__('app.shop_content_ui.configure_callout_block'))
            ->schema([
                TextInput::make('heading')
                    ->required()
                    ->maxLength(120),
                Textarea::make('message')
                    ->required()
                    ->rows(3)
                    ->maxLength(500),
                Select::make('tone')
                    ->options(['info' => 'Information', 'success' => 'Good news', 'warning' => 'Heads up'])
                    ->default('info')
                    ->required(),
            ]);
    }

    /** @param array<string, mixed> $config */
    public static function toPreviewHtml(array $config): string
    {
        return view('filament.forms.components.rich-editor.rich-content-custom-blocks.shop-callout.preview', [
            'heading' => $config['heading'] ?? 'Shop callout',
            'message' => $config['message'] ?? '',
            'tone' => $config['tone'] ?? 'info',
        ])->render();
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $data
     */
    public static function toHtml(array $config, array $data): string
    {
        return view('filament.forms.components.rich-editor.rich-content-custom-blocks.shop-callout.index', [
            'heading' => $config['heading'] ?? 'Shop callout',
            'message' => $config['message'] ?? '',
            'tone' => $config['tone'] ?? 'info',
        ])->render();
    }
}
