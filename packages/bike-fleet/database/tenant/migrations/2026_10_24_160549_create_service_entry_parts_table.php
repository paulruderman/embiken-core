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
        Schema::create('service_entry_parts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_entry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('part_id')->nullable()->constrained()->nullOnDelete();
            $table->string('part_name');
            $table->decimal('quantity', 10, 3)->default(1);
            $table->string('unit')->default('each');
            $table->timestamps();
            $table->index(['service_entry_id', 'part_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_entry_parts');
    }
};
