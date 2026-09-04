<?php

declare(strict_types=1);

namespace App\Support;

class ViewfeedRegistry
{
    /**
     * @return list<array{slug: string, name: string, component: string, inertia_page: string}>
     */
    public function all(): array
    {
        $feeds = config('embiken.viewfeeds', []);

        foreach (app(ModuleRegistry::class)->resolvedEnabledModules() as $module) {
            foreach ($module->viewfeeds() as $feed) {
                $feeds[] = $feed;
            }
        }

        return $feeds;
    }

    /**
     * @return array{slug: string, name: string, component: string, inertia_page: string}|null
     */
    public function findBySlug(string $slug): ?array
    {
        foreach ($this->all() as $feed) {
            if ($feed['slug'] === $slug) {
                return $feed;
            }
        }

        return null;
    }
}
