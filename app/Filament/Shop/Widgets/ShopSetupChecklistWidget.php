<?php

namespace App\Filament\Shop\Widgets;

use App\Filament\Shop\Support\ShopSetupReadiness;
use Filament\Widgets\Widget;

class ShopSetupChecklistWidget extends Widget
{
    public bool $showOnlyReady = false;

    protected static ?int $sort = -2;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.shop.widgets.shop-setup-checklist-widget';

    /**
     * @return array{
     *     sections: list<array{label: string, description: string, steps: list<array<string, mixed>>}>,
     *     recommendations: list<array{label: string, description: string, actions: list<array{label: string, url: string}>}>,
     *     completedCount: int,
     *     totalCount: int,
     *     progressPercent: int,
     *     essentialsComplete: bool
     * }
     */
    protected function getViewData(): array
    {
        $steps = app(ShopSetupReadiness::class)->checklist();
        $completedCount = count(array_filter($steps, fn (array $step): bool => $step['complete']));
        $totalCount = count($steps);
        $sections = [
            'fleet' => [
                'label' => 'Build your fleet',
                'description' => 'Set up the catalog and add the bikes your shop rents or services.',
                'steps' => [],
            ],
            'rentals' => [
                'label' => 'Configure rentals',
                'description' => 'Set your opening hours, rental pricing, and which bikes Book can offer.',
                'steps' => [],
            ],
        ];

        $essentialsComplete = $totalCount > 0 && $completedCount === $totalCount;

        foreach ($steps as $step) {
            if ($this->showOnlyReady && ! $essentialsComplete && ($step['complete'] || $step['blocked'])) {
                continue;
            }

            $sections[$step['section']]['steps'][] = $step;
        }

        $sections = array_values(array_filter($sections, fn (array $section): bool => $section['steps'] !== []));

        return [
            'sections' => $sections,
            'recommendations' => $completedCount === $totalCount && $totalCount > 0
                ? app(ShopSetupReadiness::class)->recommendations()
                : [],
            'completedCount' => $completedCount,
            'totalCount' => $totalCount,
            'progressPercent' => $totalCount > 0 ? (int) round($completedCount / $totalCount * 100) : 0,
            'essentialsComplete' => $essentialsComplete,
        ];
    }
}
