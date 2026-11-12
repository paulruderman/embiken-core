<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('billing_plans')
            ->where('slug', 'standard')
            ->where('processing_rate_basis_points', 800)
            ->update([
                'processing_rate_basis_points' => 400,
                'updated_at' => now(),
            ]);

        DB::table('billing_plans')
            ->where('slug', 'pro')
            ->where('processing_rate_basis_points', 500)
            ->update([
                'processing_rate_basis_points' => 300,
                'updated_at' => now(),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('billing_plans')
            ->where('slug', 'standard')
            ->where('processing_rate_basis_points', 400)
            ->update([
                'processing_rate_basis_points' => 800,
                'updated_at' => now(),
            ]);

        DB::table('billing_plans')
            ->where('slug', 'pro')
            ->where('processing_rate_basis_points', 300)
            ->update([
                'processing_rate_basis_points' => 500,
                'updated_at' => now(),
            ]);
    }
};
