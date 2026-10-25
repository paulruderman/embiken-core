<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_fleet_import_runs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('submitted_by_staff_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->string('status', 32)->index();
            $table->string('duplicate_strategy', 16);
            $table->string('partial_strategy', 24);
            $table->json('report')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_fleet_import_runs');
    }
};
