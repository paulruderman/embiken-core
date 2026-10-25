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
        Schema::create('location_metadata_import_runs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('submitted_by_staff_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->string('status', 32)->index();
            $table->json('report')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('location_metadata_import_runs');
    }
};
