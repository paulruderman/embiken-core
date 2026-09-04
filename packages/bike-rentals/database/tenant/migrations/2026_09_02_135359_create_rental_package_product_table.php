<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_package_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_package_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('bike_model_variants')->restrictOnDelete();
            $table->unsignedInteger('rate_cents');
            $table->timestamps();

            $table->unique(['rental_package_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_package_product');
    }
};
