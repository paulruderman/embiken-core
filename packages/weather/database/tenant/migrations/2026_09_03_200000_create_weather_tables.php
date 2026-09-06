<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weather_sites', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('label');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->string('nws_office')->nullable();
            $table->unsignedInteger('nws_grid_x')->nullable();
            $table->unsignedInteger('nws_grid_y')->nullable();
            $table->boolean('enabled')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('weather_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('weather_site_id')->constrained('weather_sites')->cascadeOnDelete();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->integer('temperature_f')->nullable();
            $table->integer('temperature_c')->nullable();
            $table->string('short_forecast')->nullable();
            $table->text('detailed_forecast')->nullable();
            $table->string('wind_speed')->nullable();
            $table->string('wind_direction')->nullable();
            $table->unsignedInteger('relative_humidity')->nullable();
            $table->string('icon')->nullable();
            $table->boolean('is_daytime')->nullable();
            $table->timestamps();

            $table->unique(['weather_site_id', 'starts_at']);
        });

        Schema::create('weather_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('stale_forecast_retention_minutes')->default(120);
            $table->string('radar_source')->default('rainviewer');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weather_periods');
        Schema::dropIfExists('weather_sites');
        Schema::dropIfExists('weather_settings');
    }
};
