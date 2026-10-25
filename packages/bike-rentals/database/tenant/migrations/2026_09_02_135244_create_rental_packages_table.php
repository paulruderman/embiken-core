<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();
            $table->json('name');
            $table->json('description')->nullable();
            $table->json('tagline')->nullable();
            $table->json('blurb')->nullable();
            $table->string('meter');
            $table->string('confirm_threshold');
            $table->unsignedInteger('deposit_minor_units')->nullable();
            $table->string('currency', 3)->default('usd');
            $table->unsignedTinyInteger('deposit_percent')->nullable();
            $table->unsignedInteger('min_duration_minutes')->nullable();
            $table->unsignedInteger('max_duration_minutes')->nullable();
            $table->boolean('book_visible')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_packages');
    }
};
