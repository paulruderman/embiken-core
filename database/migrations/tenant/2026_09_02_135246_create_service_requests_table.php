<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bike_id')->constrained()->restrictOnDelete();
            $table->text('description');
            $table->string('stage')->default('open');
            $table->boolean('blocks_usage')->default(true);
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('staff')->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('staff')->nullOnDelete();
            $table->foreignId('resolved_by')->nullable()->constrained('staff')->nullOnDelete();
            $table->dateTime('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['bike_id', 'stage', 'blocks_usage']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_requests');
    }
};
