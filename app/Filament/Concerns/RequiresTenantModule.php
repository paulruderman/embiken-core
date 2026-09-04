<?php

declare(strict_types=1);

namespace App\Filament\Concerns;

use App\Support\ModuleRegistry;

trait RequiresTenantModule
{
    abstract public static function tenantModuleId(): string;

    public static function canAccess(): bool
    {
        return app(ModuleRegistry::class)->isEnabled(static::tenantModuleId());
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }
}
