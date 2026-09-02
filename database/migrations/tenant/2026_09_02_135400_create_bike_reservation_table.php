<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bike_reservation', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('bike_model_variants')->restrictOnDelete();
            $table->foreignId('bike_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('assigned');
            $table->dateTime('checked_out_at')->nullable();
            $table->dateTime('checked_in_at')->nullable();
            $table->unsignedInteger('rider_height_cm')->nullable();
            $table->string('rider_name')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bike_reservation');
    }
};
