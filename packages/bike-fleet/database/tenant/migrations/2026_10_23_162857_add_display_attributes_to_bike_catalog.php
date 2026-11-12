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
        Schema::table('bike_categories', function (Blueprint $table): void {
            $table->string('interface_color')->nullable();
        });

        Schema::table('bike_models', function (Blueprint $table): void {
            $table->string('manufacturer')->nullable();
        });

        Schema::table('bike_model_variants', function (Blueprint $table): void {
            $table->string('color_name')->nullable();
            $table->string('color_value')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bike_model_variants', function (Blueprint $table): void {
            $table->dropColumn(['color_name', 'color_value']);
        });

        Schema::table('bike_models', function (Blueprint $table): void {
            $table->dropColumn('manufacturer');
        });

        Schema::table('bike_categories', function (Blueprint $table): void {
            $table->dropColumn('interface_color');
        });
    }
};
