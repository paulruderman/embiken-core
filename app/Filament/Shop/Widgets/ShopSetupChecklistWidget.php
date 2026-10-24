<?php

namespace App\Filament\Shop\Widgets;

use App\Filament\Shop\Support\ShopSetupReadiness;
use App\Support\ModuleRegistry;
use Filament\Widgets\Widget;

class ShopSetupChecklistWidget extends Widget
{
    public bool $showOnlyReady = true;

    public ?string $moduleId = null;

    protected static ?int $sort = -2;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.shop.widgets.shop-setup-checklist-widget';

    /**
     * @return array{
     *     sections: list<array{label: string, description: string, steps: list<array<string, mixed>>}>,
     *     recommendations: list<array{label: string, description: string, actions: list<array{label: string, url: string}>}>,
     *     heading: string,
     *     description: string,
     *     completedCount: int,
     *     totalCount: int,
     *     progressPercent: int,
     *     essentialsComplete: bool
     * }
     */
    protected function getViewData(): array
    {
        $readiness = app(ShopSetupReadiness::class);
        $moduleRegistry = app(ModuleRegistry::class);
        $steps = $readiness->checklist($this->moduleId);
        $completedCount = count(array_filter($steps, fn (array $step): bool => $step['complete']));
        $totalCount = count($steps);
        $sectionSteps = [];

        $essentialsComplete = $totalCount > 0 && $completedCount === $totalCount;

        foreach ($steps as $step) {
            if ($this->showOnlyReady && ! $essentialsComplete && ($step['complete'] || $step['blocked'])) {
                continue;
            }

            $moduleId = $step['module_id'];
            $module = $moduleRegistry->find($moduleId);

            if ($module === null) {
                continue;
            }

            $sectionSteps[$moduleId] ??= [
                'label' => $module->label(),
                'description' => $module->description(),
                'steps' => [],
            ];
            $sectionSteps[$moduleId]['steps'][] = $step;
        }

        $sections = array_values($sectionSteps);
        $module = $this->moduleId === null ? null : $moduleRegistry->find($this->moduleId);

        return [
            'sections' => $sections,
            'recommendations' => $completedCount === $totalCount && $totalCount > 0
                ? $readiness->recommendations($this->moduleId)
                : [],
            'heading' => $module === null
                ? (string) __('app.setup_checklist.heading')
                : (string) __('app.setup_checklist.module_heading', ['module' => $module->label()]),
            'description' => $module === null
                ? (string) __('app.setup_checklist.description')
                : (string) __('app.setup_checklist.module_description', ['module' => $module->label()]),
            'completedCount' => $completedCount,
            'totalCount' => $totalCount,
            'progressPercent' => $totalCount > 0 ? (int) round($completedCount / $totalCount * 100) : 0,
            'essentialsComplete' => $essentialsComplete,
        ];
    }
}
