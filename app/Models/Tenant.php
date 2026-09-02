<?php

namespace App\Models;

use Database\Factories\TenantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

/**
 * @property string $id
 * @property string $name
 * @property Carbon|null $suspended_at
 * @property string|null $stripe_connect_account_id
 * @property bool $charges_enabled
 * @property bool $transfers_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
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
            'suspended_at',
            'stripe_connect_account_id',
            'charges_enabled',
            'transfers_active',
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
        ];
    }

    public function isSuspended(): bool
    {
        return $this->suspended_at !== null;
    }
}
