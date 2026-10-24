<?php

namespace App\Filament\Shop\Pages;

use App\Filament\Concerns\RequiresTenantModule;
use App\Filament\Shop\Widgets\ShopSetupChecklistWidget;
use Filament\Pages\Page;

abstract class ModuleSetupDashboard extends Page
{
    use RequiresTenantModule;

    protected string $view = 'filament.shop.pages.module-setup-dashboard';

    /**
     * @return array<class-string>
     */
    protected function getHeaderWidgets(): array
    {
        return [ShopSetupChecklistWidget::class];
    }

    /**
     * @return array<string, string>
     */
    public function getWidgetData(): array
    {
        return [
            'moduleId' => static::tenantModuleId(),
        ];
    }
}
