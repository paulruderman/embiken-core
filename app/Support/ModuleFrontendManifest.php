<?php

declare(strict_types=1);

namespace App\Support;

class ModuleFrontendManifest
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $aliases = [];
        $entrypoints = [];
        $pinia = [];
        $viewfeeds = [];
        $inertia = [];

        foreach (app(ModuleRegistry::class)->installedModules() as $module) {
            if ($module->viteAlias() !== null && $module->frontendRoot() !== null) {
                $aliases[$module->viteAlias()] = $module->frontendRoot();
            }

            foreach ($module->viteEntrypoints() as $entrypoint) {
                $entrypoints[] = $entrypoint;
            }

            if ($module->piniaModule() !== null) {
                $pinia[$module->id()] = $module->piniaModule();
            }

            if ($module->inertiaPagePrefix() !== null && $module->frontendRoot() !== null) {
                $inertia[$module->inertiaPagePrefix()] = $module->frontendRoot();
            }

            foreach ($module->viewfeeds() as $feed) {
                $viewfeeds[] = $feed;
            }
        }

        return [
            'aliases' => $aliases,
            'viteEntrypoints' => $entrypoints,
            'piniaModules' => $pinia,
            'viewfeeds' => $viewfeeds,
            'inertiaPrefixes' => $inertia,
        ];
    }

    public function write(): string
    {
        $path = resource_path('js/generated/modules-manifest.json');
        $directory = dirname($path);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($path, json_encode($this->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");

        return $path;
    }
}
