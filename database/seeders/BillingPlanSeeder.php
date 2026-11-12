<?php

namespace Database\Seeders;

use App\Models\BillingPlan;
use Illuminate\Database\Seeder;

class BillingPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ([
            [
                'slug' => 'starter',
                'name' => 'Starter',
                'monthly_amount_cents' => null,
                'annual_amount_cents' => null,
                'processing_rate_basis_points' => 1500,
                'custom_pricing' => false,
                'sort_order' => 1,
                'features' => ['No subscription fee', 'Shop workspace and Manager invitation', '15% combined rate on payments Embiken processes'],
            ],
            [
                'slug' => 'standard',
                'name' => 'Standard',
                'monthly_amount_cents' => 3900,
                'annual_amount_cents' => 42000,
                'processing_rate_basis_points' => 400,
                'custom_pricing' => false,
                'sort_order' => 2,
                'features' => ['Subscription billing is not active yet', '4% combined rate on payments Embiken processes'],
            ],
            [
                'slug' => 'pro',
                'name' => 'Pro',
                'monthly_amount_cents' => 29900,
                'annual_amount_cents' => 300000,
                'processing_rate_basis_points' => 300,
                'custom_pricing' => false,
                'sort_order' => 3,
                'features' => ['Subscription billing is not active yet', '3% combined rate on payments Embiken processes'],
            ],
            [
                'slug' => 'enterprise',
                'name' => 'Enterprise',
                'monthly_amount_cents' => null,
                'annual_amount_cents' => null,
                'processing_rate_basis_points' => null,
                'custom_pricing' => true,
                'sort_order' => 4,
                'features' => ['Discuss custom pricing and current early-access availability'],
            ],
        ] as $plan) {
            BillingPlan::query()->firstOrCreate(
                ['slug' => $plan['slug']],
                [...$plan, 'currency' => 'usd', 'is_public' => true],
            );
        }
    }
}
