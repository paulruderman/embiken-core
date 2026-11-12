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

        Schema::table('bike_categories', function (Blueprint $table): void {
            $table->json('name')->change();
            $table->json('description')->nullable()->change();
        });
        Schema::table('bike_models', function (Blueprint $table): void {
            $table->json('name')->change();
            $table->json('description')->nullable()->change();
        });
        Schema::table('bike_model_variants', function (Blueprint $table): void {
            $table->json('color_name')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bike_categories', function (Blueprint $table): void {
            $table->string('name')->change();
            $table->text('description')->nullable()->change();
        });
        Schema::table('bike_models', function (Blueprint $table): void {
            $table->string('name')->change();
            $table->text('description')->nullable()->change();
        });
        Schema::table('bike_model_variants', function (Blueprint $table): void {
            $table->string('color_name')->nullable()->change();
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
        foreach ([
            'bike_categories' => ['name', 'description'],
            'bike_models' => ['name', 'description'],
            'bike_model_variants' => ['color_name'],
        ] as $tableName => $columns) {
            foreach (DB::table($tableName)->get() as $record) {
                $updates = [];

                foreach ($columns as $column) {
                    if ($record->{$column} !== null) {
                        $updates[$column] = $map($record->{$column});
                    }
                }

                if ($updates !== []) {
                    DB::table($tableName)->where('id', $record->id)->update($updates);
                }
            }
        }
    }
};
