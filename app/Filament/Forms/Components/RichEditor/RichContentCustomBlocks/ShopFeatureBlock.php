<?php

namespace App\Filament\Forms\Components\RichEditor\RichContentCustomBlocks;

use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

class ShopFeatureBlock extends RichContentCustomBlock
{
    public static function getId(): string
    {
        return 'shop_feature';
    }

    public static function getLabel(): string
    {
        return 'Shop feature';
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalDescription('Configure the shop feature block')
            ->schema([
                TextInput::make('heading')
                    ->required()
                    ->maxLength(120),
                Textarea::make('description')
                    ->required()
                    ->rows(3)
                    ->maxLength(500),
            ]);
    }

    /** @param array<string, mixed> $config */
    public static function toPreviewHtml(array $config): string
    {
        return view('filament.forms.components.rich-editor.rich-content-custom-blocks.shop-feature.preview', [
            'heading' => $config['heading'] ?? 'Shop feature',
            'description' => $config['description'] ?? '',
        ])->render();
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $data
     */
    public static function toHtml(array $config, array $data): string
    {
        return view('filament.forms.components.rich-editor.rich-content-custom-blocks.shop-feature.index', [
            'heading' => $config['heading'] ?? 'Shop feature',
            'description' => $config['description'] ?? '',
        ])->render();
    }
}
