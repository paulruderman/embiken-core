<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();
            $table->foreignId('display_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('paired_terminal_device_id')->nullable()->constrained('devices')->nullOnDelete();
            $table->string('name');
            $table->string('surface');
            $table->string('pairing_code_hash')->nullable()->unique();
            $table->timestamp('pairing_expires_at')->nullable();
            $table->timestamp('paired_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->index(['location_id', 'surface']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
