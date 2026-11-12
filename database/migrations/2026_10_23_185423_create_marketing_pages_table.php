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
        Schema::create('marketing_pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('page_type')->default('page')->index();
            $table->string('title');
            $table->string('excerpt', 500)->nullable();
            $table->longText('content_html');
            $table->string('seo_title')->nullable();
            $table->string('seo_description', 320)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketing_pages');
    }
};
