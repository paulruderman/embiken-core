<?php

namespace App\Models;

use Database\Factories\BillingPlanFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BillingPlan extends Model
{
    /** @use HasFactory<BillingPlanFactory> */
    use HasFactory;

    protected $primaryKey = 'slug';

    public $incrementing = false;

    protected $keyType = 'string';

    /** @var list<string> */
    protected $fillable = [
        'slug',
        'name',
        'monthly_amount_cents',
        'annual_amount_cents',
        'currency',
        'processing_rate_basis_points',
        'custom_pricing',
        'is_public',
        'sort_order',
        'features',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'monthly_amount_cents' => 'integer',
            'annual_amount_cents' => 'integer',
            'processing_rate_basis_points' => 'integer',
            'custom_pricing' => 'boolean',
            'is_public' => 'boolean',
            'sort_order' => 'integer',
            'features' => 'array',
        ];
    }

    /** @return HasMany<Tenant, $this> */
    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class, 'billing_plan_id', 'slug');
    }

    public function processingRateLabel(): string
    {
        return $this->processing_rate_basis_points === null
            ? 'Custom processing rate'
            : rtrim(rtrim(number_format($this->processing_rate_basis_points / 100, 2), '0'), '.').'%';
    }
}
