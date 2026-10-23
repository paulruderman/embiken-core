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
        Schema::table('bike_model_variants', function (Blueprint $table): void {
            $table->dropColumn(['min_extended_rider_height', 'max_extended_rider_height']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bike_model_variants', function (Blueprint $table): void {
            $table->unsignedInteger('min_extended_rider_height')->nullable();
            $table->unsignedInteger('max_extended_rider_height')->nullable();
        });
    }
};
