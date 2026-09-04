<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('rental_package_id')->nullable()->constrained()->nullOnDelete();
            $table->string('channel');
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('stage');
            $table->unsignedInteger('owed')->default(0);
            $table->unsignedInteger('paid')->default(0);
            $table->dateTime('expires_at')->nullable();
            $table->dateTime('waiver_accepted_at')->nullable();
            $table->text('notes')->nullable();
            $table->text('damage_notes')->nullable();
            $table->string('myrental_token')->nullable()->unique();
            $table->timestamps();

            $table->index(['starts_at', 'ends_at']);
            $table->index('stage');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
