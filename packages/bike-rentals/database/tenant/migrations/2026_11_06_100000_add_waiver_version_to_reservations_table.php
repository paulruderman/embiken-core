<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table): void {
            $table->foreignId('waiver_version_id')->nullable()->constrained('location_waivers')->nullOnDelete();
            $table->string('waiver_locale', 5)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('waiver_version_id');
            $table->dropColumn('waiver_locale');
        });
    }
};
