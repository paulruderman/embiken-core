<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (DB::table('tenants')->select(['id', 'modules'])->orderBy('id')->cursor() as $tenant) {
            if ($tenant->modules === null) {
                continue;
            }

            $modules = json_decode($tenant->modules, true);

            if (! is_array($modules)
                || ! in_array('embiken/bike-rentals', $modules, true)
                || in_array('embiken/bike-rental-self-booking', $modules, true)) {
                continue;
            }

            $modules[] = 'embiken/bike-rental-self-booking';

            DB::table('tenants')
                ->where('id', $tenant->id)
                ->update(['modules' => json_encode(array_values($modules), JSON_THROW_ON_ERROR)]);
        }
    }

    public function down(): void
    {
        // Preserve module choices made after the backfill.
    }
};
