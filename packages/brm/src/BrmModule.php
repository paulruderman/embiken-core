<?php

declare(strict_types=1);

namespace Embiken\Brm;

use App\Support\AbstractTenantModule;

class BrmModule extends AbstractTenantModule
{
    public const ID = 'embiken/brm';

    public function id(): string
    {
        return self::ID;
    }

    public function label(): string
    {
        return 'BRM importer';
    }

    public function description(): string
    {
        return 'Import existing bike rental data from BRM.';
    }
}
