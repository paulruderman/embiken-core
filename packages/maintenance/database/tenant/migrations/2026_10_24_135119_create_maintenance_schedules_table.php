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
        Schema::create('maintenance_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bike_id')->constrained()->cascadeOnDelete();
            $table->foreignId('maintenance_checklist_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->date('due_at');
            $table->string('status')->default('scheduled');
            $table->json('checklist_items')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['bike_id', 'due_at']);
            $table->index(['status', 'due_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_schedules');
    }
};
