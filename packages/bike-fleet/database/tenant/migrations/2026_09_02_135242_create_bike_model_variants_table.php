<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bike_model_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bike_model_id')->constrained()->cascadeOnDelete();
            $table->string('size');
            $table->unsignedInteger('min_ideal_rider_height')->nullable();
            $table->unsignedInteger('max_ideal_rider_height')->nullable();
            $table->unsignedInteger('min_extended_rider_height')->nullable();
            $table->unsignedInteger('max_extended_rider_height')->nullable();
            $table->string('photo')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bike_model_variants');
    }
};
