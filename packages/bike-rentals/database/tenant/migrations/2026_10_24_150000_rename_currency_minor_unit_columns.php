<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rental_package_product', function (Blueprint $table): void {
            $table->renameColumn('rate_cents', 'rate_minor_units');
        });

        Schema::table('rental_packages', function (Blueprint $table): void {
            $table->renameColumn('deposit_cents', 'deposit_minor_units');
        });

        Schema::table('transactions', function (Blueprint $table): void {
            $table->renameColumn('amount_cents', 'amount_minor_units');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table): void {
            $table->renameColumn('amount_minor_units', 'amount_cents');
        });

        Schema::table('rental_packages', function (Blueprint $table): void {
            $table->renameColumn('deposit_minor_units', 'deposit_cents');
        });

        Schema::table('rental_package_product', function (Blueprint $table): void {
            $table->renameColumn('rate_minor_units', 'rate_cents');
        });
    }
};
