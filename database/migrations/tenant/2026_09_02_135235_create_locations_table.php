<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('timezone');
            $table->string('currency')->default('usd');
            $table->unsignedInteger('minimum_turnaround_buffer_minutes')->default(10);
            $table->string('bike_assignment_policy')->default('terminal');
            $table->string('return_situation')->default('home');
            $table->unsignedInteger('reservation_readiness_window_minutes')->default(45);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
