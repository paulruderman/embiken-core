<?php

namespace App\Models;

use App\Support\ModuleRegistry;
use Database\Factories\TenantFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

/**
 * @property string $id
 * @property string $name
 * @property string|null $manager_name
 * @property string|null $manager_email
 * @property Carbon|null $suspended_at
 * @property string|null $stripe_connect_account_id
 * @property bool $charges_enabled
 * @property bool $transfers_active
 * @property list<string>|null $modules
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Domain> $domains
 */
class Tenant extends BaseTenant implements TenantWithDatabase
{
    /** @use HasFactory<TenantFactory> */
    use HasDatabase, HasDomains, HasFactory;

    /**
     * @return list<string>
     */
    public static function getCustomColumns(): array
    {
        return [
            'id',
            'name',
            'manager_name',
            'manager_email',
            'suspended_at',
            'stripe_connect_account_id',
            'charges_enabled',
            'transfers_active',
            'modules',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'suspended_at' => 'datetime',
            'charges_enabled' => 'boolean',
            'transfers_active' => 'boolean',
            'modules' => 'array',
        ];
    }

    /**
     * @return HasMany<Domain, $this>
     */
    public function domains(): HasMany
    {
        return $this->hasMany(Domain::class, 'tenant_id');
    }

    /**
     * @return list<string>
     */
    public function moduleIds(): array
    {
        $modules = $this->modules;

        if (! is_array($modules) || $modules === []) {
            return ModuleRegistry::defaultTenantModuleIds();
        }

        return array_values(array_filter($modules, fn (mixed $id): bool => is_string($id)));
    }

    public function isSuspended(): bool
    {
        return $this->suspended_at !== null;
    }
}
