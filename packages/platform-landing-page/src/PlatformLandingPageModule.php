<?php

declare(strict_types=1);

namespace Embiken\PlatformLandingPage;

use App\Enums\ModuleScope;
use App\Support\AbstractTenantModule;

class PlatformLandingPageModule extends AbstractTenantModule
{
    public const ID = 'embiken/platform-landing-page';

    public function id(): string
    {
        return self::ID;
    }

    public function label(): string
    {
        return 'Platform landing page';
    }

    public function description(): string
    {
        return 'Platform landing page.';
    }

    public function scope(): ModuleScope
    {
        return ModuleScope::Platform;
    }
}
