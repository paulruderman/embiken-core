<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table): void {
            $table->string('currency', 3)->default('usd');
        });

        foreach (DB::table('locations')->select(['id', 'currency'])->get() as $location) {
            DB::table('reservations')
                ->where('location_id', $location->id)
                ->update(['currency' => strtolower($location->currency)]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table): void {
            $table->dropColumn('currency');
        });
    }
};
