<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bikes', function (Blueprint $table) {
            $table->foreign('bike_situation_reservation_id')
                ->references('id')
                ->on('reservations')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('bikes', function (Blueprint $table) {
            $table->dropForeign(['bike_situation_reservation_id']);
        });
    }
};
