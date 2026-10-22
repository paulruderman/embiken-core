<?php

namespace App\Filament\Shop\Widgets;

use App\Filament\Shop\Support\ShopSetupReadiness;
use Filament\Widgets\Widget;

class ShopSetupChecklistWidget extends Widget
{
    protected static ?int $sort = -2;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.shop.widgets.shop-setup-checklist-widget';

    /**
     * @return array{steps: list<array<string, mixed>>, completedCount: int, totalCount: int}
     */
    protected function getViewData(): array
    {
        $steps = app(ShopSetupReadiness::class)->checklist();

        return [
            'steps' => $steps,
            'completedCount' => count(array_filter($steps, fn (array $step): bool => $step['complete'])),
            'totalCount' => count($steps),
        ];
    }
}
