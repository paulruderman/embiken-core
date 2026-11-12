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
        $this->mapColumns(static fn (mixed $value): string => json_encode(['en' => $value], JSON_THROW_ON_ERROR));

        Schema::table('rental_packages', function (Blueprint $table): void {
            $table->json('name')->change();
            $table->json('description')->nullable()->change();
            $table->json('tagline')->nullable()->change();
            $table->json('blurb')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rental_packages', function (Blueprint $table): void {
            $table->string('name')->change();
            $table->text('description')->nullable()->change();
            $table->string('tagline')->nullable()->change();
            $table->text('blurb')->nullable()->change();
        });

        $this->mapColumns(static function (mixed $value): ?string {
            if ($value === null) {
                return null;
            }

            $translations = json_decode((string) $value, true);

            return $translations['en'] ?? '';
        });
    }

    private function mapColumns(Closure $map): void
    {
        foreach (DB::table('rental_packages')->get() as $package) {
            $updates = [];

            foreach (['name', 'description', 'tagline', 'blurb'] as $column) {
                if ($package->{$column} !== null) {
                    $updates[$column] = $map($package->{$column});
                }
            }

            if ($updates !== []) {
                DB::table('rental_packages')->where('id', $package->id)->update($updates);
            }
        }
    }
};
