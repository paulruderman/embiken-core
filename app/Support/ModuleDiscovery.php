<?php

declare(strict_types=1);

namespace App\Support;

use App\Contracts\TenantModule;
use Composer\InstalledVersions;
use Illuminate\Support\Str;

class ModuleDiscovery
{
    /**
     * @return list<class-string<TenantModule>>
     */
    public function installedModuleClasses(): array
    {
        $classes = [];

        foreach (InstalledVersions::getInstalledPackages() as $packageName) {
            if (! str_starts_with($packageName, 'embiken/')) {
                continue;
            }

            $class = $this->resolveModuleClass($packageName);

            if (is_string($class) && is_subclass_of($class, TenantModule::class)) {
                $classes[] = $class;
            }
        }

        sort($classes);

        return $classes;
    }

    public function resolveModuleClass(string $packageName): ?string
    {
        $slug = Str::after($packageName, 'embiken/');
        $studly = Str::studly($slug);

        $class = "Embiken\\{$studly}\\{$studly}Module";

        return class_exists($class) ? $class : null;
    }
}
