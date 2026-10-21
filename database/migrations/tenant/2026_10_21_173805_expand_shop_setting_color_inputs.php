<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('shop_settings', function (Blueprint $table): void {
            $table->string('primary_color', 32)->nullable()->change();
            $table->string('brand_gray_color', 32)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shop_settings', function (Blueprint $table): void {
            $table->string('primary_color', 7)->nullable()->change();
            $table->string('brand_gray_color', 7)->nullable()->change();
        });
    }
};
