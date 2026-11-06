<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table): void {
            $table->unsignedBigInteger('focused_reservation_id')->nullable()->after('paired_terminal_device_id');
            $table->index(['surface', 'focused_reservation_id']);
        });
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table): void {
            $table->dropIndex(['surface', 'focused_reservation_id']);
            $table->dropColumn('focused_reservation_id');
        });
    }
};
