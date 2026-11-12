<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('early_access_signups', function (Blueprint $table): void {
            $table->id();
            $table->string('email')->unique();
            $table->string('name')->nullable();
            $table->string('shop_name')->nullable();
            $table->text('message')->nullable();
            $table->string('status')->default('new')->index();
            $table->string('locale', 8)->default('en');
            $table->text('internal_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('early_access_signups');
    }
};
