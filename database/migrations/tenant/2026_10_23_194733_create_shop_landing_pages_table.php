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
        Schema::create('shop_landing_pages', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->string('layout', 20)->default('hero');
            $table->json('layout_data');
            $table->string('theme', 20)->default('light');
            $table->json('custom_content')->nullable();
            $table->json('booking_notices')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shop_landing_pages');
    }
};
