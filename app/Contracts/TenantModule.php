<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Enums\ModuleScope;
use BackedEnum;

interface TenantModule
{
    public function id(): string;

    public function label(): string;

    public function description(): string;

    public function scope(): ModuleScope;

    public function viteAlias(): ?string;

    public function frontendRoot(): ?string;

    public function inertiaPagePrefix(): ?string;

    public function registerApplication(): void;

    public function registerForTenant(): void;

    /**
     * @return list<string>
     */
    public function tenantMigrationPaths(): array;

    /**
     * @return list<array{path: string, namespace: string}>
     */
    public function filamentResourceDiscoveryPaths(): array;

    /**
     * @return list<array{path: string, namespace: string}>
     */
    public function filamentPageDiscoveryPaths(): array;

    /**
     * @return list<array{slug: string, name: string, component: string, inertia_page: string}>
     */
    public function viewfeeds(): array;

    /**
     * @return list<string>
     */
    public function viteEntrypoints(): array;

    /**
     * @return list<string>
     */
    public function dependencies(): array;

    /**
     * @return list<class-string<BackedEnum>>
     */
    public function frontendEnums(): array;

    /**
     * @return class-string<DayStoreContributor>|null
     */
    public function dayStoreContributor(): ?string;

    /**
     * @return array{namespace: string, import: string}|null
     */
    public function piniaModule(): ?array;
}
