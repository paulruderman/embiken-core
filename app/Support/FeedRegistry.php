<?php

declare(strict_types=1);

namespace App\Support;

use App\Contracts\WallDisplayFeedDataProvider;

class FeedRegistry
{
    /**
     * @return list<array{slug: string, name: string, component: string, provider?: class-string<WallDisplayFeedDataProvider>}>
     */
    public function all(): array
    {
        /** @var list<array{slug: string, name: string, component: string, provider?: class-string<WallDisplayFeedDataProvider>}> $feeds */
        $feeds = [
            [
                'slug' => 'today-shop-board',
                'name' => __('app.wall_display_ui.today_shop_board'),
                'component' => 'core:today-shop-board',
            ],
            ...config('embiken.feeds', []),
        ];

        foreach (app(ModuleRegistry::class)->resolvedEnabledModules() as $module) {
            foreach ($module->feeds() as $feed) {
                $feeds[] = $feed;
            }
        }

        return $feeds;
    }

    /**
     * @return array{slug: string, name: string, component: string, provider?: class-string<WallDisplayFeedDataProvider>}|null
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
