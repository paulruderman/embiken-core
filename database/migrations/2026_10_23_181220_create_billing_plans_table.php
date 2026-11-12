<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('billing_plans', function (Blueprint $table) {
            $table->string('slug')->primary();
            $table->string('name');
            $table->unsignedInteger('monthly_amount_cents')->nullable();
            $table->unsignedInteger('annual_amount_cents')->nullable();
            $table->char('currency', 3)->default('usd');
            $table->unsignedSmallInteger('processing_rate_basis_points')->nullable();
            $table->boolean('custom_pricing')->default(false);
            $table->boolean('is_public')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->json('features')->nullable();
            $table->timestamps();
        });

        $now = now();
        DB::table('billing_plans')->insert([
            ['slug' => 'starter', 'name' => 'Starter', 'monthly_amount_cents' => null, 'annual_amount_cents' => null, 'currency' => 'usd', 'processing_rate_basis_points' => 1500, 'custom_pricing' => false, 'is_public' => true, 'sort_order' => 1, 'features' => json_encode(['Free fleet management', 'Workshop tools', 'Rental operations']), 'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'standard', 'name' => 'Standard', 'monthly_amount_cents' => 3900, 'annual_amount_cents' => 42000, 'currency' => 'usd', 'processing_rate_basis_points' => 400, 'custom_pricing' => false, 'is_public' => true, 'sort_order' => 2, 'features' => json_encode(['Everything in Starter', 'Lower payment processing rate']), 'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'pro', 'name' => 'Pro', 'monthly_amount_cents' => 29900, 'annual_amount_cents' => 300000, 'currency' => 'usd', 'processing_rate_basis_points' => 300, 'custom_pricing' => false, 'is_public' => true, 'sort_order' => 3, 'features' => json_encode(['Everything in Standard', 'Lowest published processing rate']), 'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'enterprise', 'name' => 'Enterprise', 'monthly_amount_cents' => null, 'annual_amount_cents' => null, 'currency' => 'usd', 'processing_rate_basis_points' => null, 'custom_pricing' => true, 'is_public' => true, 'sort_order' => 4, 'features' => json_encode(['Custom pricing and support']), 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('billing_plans');
    }
};
