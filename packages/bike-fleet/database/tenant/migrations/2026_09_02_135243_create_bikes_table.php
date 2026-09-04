<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bikes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bike_model_variant_id')->constrained()->restrictOnDelete();
            $table->string('bid')->unique();
            $table->boolean('in_service')->default(true);
            $table->boolean('self_bookable')->default(true);
            $table->string('bike_situation_state')->default('home');
            $table->unsignedBigInteger('bike_situation_reservation_id')->nullable();
            $table->string('photo')->nullable();
            $table->text('damage_notes')->nullable();
            $table->timestamps();

            $table->index('bike_situation_state');
            $table->index('bike_situation_reservation_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bikes');
    }
};
