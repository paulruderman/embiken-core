<?php

declare(strict_types=1);

namespace App\Enums;

enum ModuleScope: string
{
    case Tenant = 'tenant';
    case Platform = 'platform';
}
