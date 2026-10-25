<?php

declare(strict_types=1);

namespace Embiken\BikeFleet\Services;

use App\Models\Location;
use App\Support\BrandColor;
use Embiken\BikeFleet\Enums\BikeSituation;
use Embiken\BikeFleet\Models\Bike;
use Embiken\BikeFleet\Models\BikeCategory;
use Embiken\BikeFleet\Models\BikeModel;
use Embiken\BikeFleet\Models\BikeModelVariant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CatalogFleetImport
{
    private const array FILES = [
        'categories' => ['key', 'name', 'description', 'interface_color'],
        'models' => ['key', 'category_key', 'name', 'manufacturer', 'description', 'padding_minutes'],
        'variants' => ['key', 'model_key', 'size', 'color_name', 'color_value', 'min_ideal_rider_height', 'max_ideal_rider_height', 'min_extended_rider_height', 'max_extended_rider_height'],
        'bikes' => ['bid', 'variant_key', 'serial_number', 'in_service', 'self_bookable'],
    ];

    /** @return array<string, string> */
    public function templates(): array
    {
        return array_map(static fn (array $headers): string => implode(',', $headers)."\n", self::FILES);
    }

    /**
     * @param  array<string, string>  $files  CSV contents keyed by categories, models, variants, bikes.
     * @return array{summary: array<string, array{create: int, update: int, skip: int, conflict: int, invalid: int}>, issues: list<array{file: string, row: int|null, message: string}>, can_apply: bool, valid_rows: int}
     */
    public function preview(array $files, string $duplicateStrategy, string $partialStrategy): array
    {
        return $this->analyze($files, $duplicateStrategy, $partialStrategy)['report'];
    }

    /**
     * @param  array<string, string>  $files
     * @return array{summary: array<string, array{create: int, update: int, skip: int, conflict: int, invalid: int}>, issues: list<array{file: string, row: int|null, message: string}>, can_apply: bool, valid_rows: int}
     */
    public function validateFiles(array $files): array
    {
        return $this->analyze($files, 'report', 'all-or-nothing', checkExistingRecords: false)['report'];
    }

    /**
     * @param  array<string, string>  $files
     * @return array{summary: array<string, array{create: int, update: int, skip: int, conflict: int, invalid: int}>, issues: list<array{file: string, row: int|null, message: string}>, can_apply: bool, valid_rows: int, applied: bool}
     */
    public function apply(array $files, string $duplicateStrategy, string $partialStrategy): array
    {
        return DB::transaction(function () use ($files, $duplicateStrategy, $partialStrategy): array {
            $analysis = $this->analyze($files, $duplicateStrategy, $partialStrategy);
            $report = $analysis['report'];

            if (! $report['can_apply']) {
                return [...$report, 'applied' => false];
            }

            $location = Location::query()->firstOrFail();
            $created = [
                'categories' => [],
                'models' => [],
                'variants' => [],
            ];

            foreach (['categories', 'models', 'variants', 'bikes'] as $file) {
                foreach ($analysis['rows'][$file] ?? [] as $key => $entry) {
                    if (! in_array($entry['operation'], ['create', 'update'], true)) {
                        if ($file !== 'bikes' && $entry['matched'] instanceof Model) {
                            $created[$file][$key] = $entry['matched'];
                        }

                        continue;
                    }

                    $attributes = $entry['attributes'];

                    if ($file === 'models') {
                        $attributes['bike_category_id'] = $created['categories'][$entry['row']['category_key']]->getKey();
                    } elseif ($file === 'variants') {
                        $attributes['bike_model_id'] = $created['models'][$entry['row']['model_key']]->getKey();
                    } elseif ($file === 'bikes') {
                        $attributes['location_id'] = $location->getKey();
                        $attributes['bike_model_variant_id'] = $created['variants'][$entry['row']['variant_key']]->getKey();
                    }

                    $modelClass = match ($file) {
                        'categories' => BikeCategory::class,
                        'models' => BikeModel::class,
                        'variants' => BikeModelVariant::class,
                        'bikes' => Bike::class,
                    };
                    $record = $entry['matched'];

                    if ($entry['operation'] === 'create') {
                        $record = $modelClass::query()->create($attributes);
                    } else {
                        $record->fill($attributes)->save();
                    }

                    if ($file !== 'bikes') {
                        $created[$file][$key] = $record;
                    }
                }
            }

            return [...$report, 'applied' => true];
        }, attempts: 3);
    }

    /**
     * @param  array<string, string>  $files
     * @return array{
     *     report: array{summary: array<string, array{create: int, update: int, skip: int, conflict: int, invalid: int}>, issues: list<array{file: string, row: int|null, message: string}>, can_apply: bool, valid_rows: int},
     *     rows: array<string, array<string, array{row: array<string, string>, attributes: array<string, mixed>, matched: Model|null, operation: string}>>
     * }
     */
    private function analyze(array $files, string $duplicateStrategy, string $partialStrategy, bool $checkExistingRecords = true): array
    {
        $summary = [
            'categories' => ['create' => 0, 'update' => 0, 'skip' => 0, 'conflict' => 0, 'invalid' => 0],
            'models' => ['create' => 0, 'update' => 0, 'skip' => 0, 'conflict' => 0, 'invalid' => 0],
            'variants' => ['create' => 0, 'update' => 0, 'skip' => 0, 'conflict' => 0, 'invalid' => 0],
            'bikes' => ['create' => 0, 'update' => 0, 'skip' => 0, 'conflict' => 0, 'invalid' => 0],
        ];
        $issues = [];
        $plannedRows = ['categories' => [], 'models' => [], 'variants' => [], 'bikes' => []];
        $validRows = 0;

        foreach (self::FILES as $file => $headers) {
            $parsedRows = $this->parseFile($file, $headers, $files[$file] ?? null, $issues);
            $seenKeys = [];
            $seenIdentities = [];

            foreach ($parsedRows as $row) {
                $key = $row['values']['key'] ?? $row['values']['bid'] ?? '';
                $planKey = $key;
                $rowIssues = $this->validateRow($file, $row['values']);

                if ($key !== '' && isset($seenKeys[$key])) {
                    $rowIssues[] = "Duplicate key '{$key}' in this file.";
                    $planKey = $key.'#row'.$row['line'];
                }
                if ($key !== '') {
                    $seenKeys[$key] = true;
                }

                $parentKey = match ($file) {
                    'models' => 'category_key',
                    'variants' => 'model_key',
                    'bikes' => 'variant_key',
                    default => null,
                };
                $parentFile = match ($file) {
                    'models' => 'categories',
                    'variants' => 'models',
                    'bikes' => 'variants',
                    default => null,
                };
                $parentEntry = $parentKey === null
                    ? null
                    : ($plannedRows[$parentFile][$row['values'][$parentKey] ?? ''] ?? null);
                if ($parentKey !== null && ($parentEntry === null || $parentEntry['operation'] === 'invalid')) {
                    $rowIssues[] = "The referenced {$parentKey} was not found or is invalid.";
                }

                $attributes = $this->attributes($file, $row['values']);
                $matched = $rowIssues === [] && $checkExistingRecords
                    ? $this->findMatch($file, $row['values'], $plannedRows)
                    : null;
                $identity = $this->identity($file, $row['values']);

                if ($rowIssues === [] && isset($seenIdentities[$identity])) {
                    $rowIssues[] = 'Another row in this file describes the same catalog or fleet record.';
                }
                if ($rowIssues === []) {
                    $seenIdentities[$identity] = true;
                }

                $operation = 'create';
                if ($rowIssues !== []) {
                    $operation = 'invalid';
                    $summary[$file]['invalid']++;
                    foreach ($rowIssues as $message) {
                        $issues[] = ['file' => $file, 'row' => $row['line'], 'message' => $message];
                    }
                } elseif ($matched instanceof Model) {
                    $operation = match ($duplicateStrategy) {
                        'update' => 'update',
                        'skip' => 'skip',
                        default => 'conflict',
                    };
                    $countKey = match ($operation) {
                        'conflict' => 'conflict',
                        'skip' => 'skip',
                        default => 'update',
                    };
                    $summary[$file][$countKey]++;

                    if ($operation === 'conflict') {
                        $issues[] = ['file' => $file, 'row' => $row['line'], 'message' => 'A matching record already exists.'];
                    }
                } else {
                    $summary[$file]['create']++;
                }

                if ($operation !== 'invalid') {
                    $validRows++;
                }
                $plannedRows[$file][$planKey] = [
                    'row' => $row['values'],
                    'attributes' => $attributes,
                    'matched' => $matched,
                    'operation' => $operation,
                ];
            }
        }

        $hasIssues = $issues !== [];
        $canApply = $validRows > 0 && ($partialStrategy === 'partial' || ! $hasIssues);

        return [
            'report' => [
                'summary' => $summary,
                'issues' => $issues,
                'can_apply' => $canApply,
                'valid_rows' => $validRows,
            ],
            'rows' => $plannedRows,
        ];
    }

    /** @param list<string> $headers
     *  @param list<array{file: string, row: int|null, message: string}> $issues
     *  @return list<array{line: int, values: array<string, string>}> */
    private function parseFile(string $file, array $headers, ?string $content, array &$issues): array
    {
        if ($content === null || trim($content) === '') {
            $issues[] = ['file' => $file, 'row' => null, 'message' => 'A non-empty CSV file is required.'];

            return [];
        }

        $stream = fopen('php://temp', 'r+');
        if ($stream === false) {
            $issues[] = ['file' => $file, 'row' => null, 'message' => 'The CSV could not be read.'];

            return [];
        }
        fwrite($stream, $content);
        rewind($stream);
        $headerRow = fgetcsv($stream, null, ',', '"', '');

        if (! is_array($headerRow)) {
            fclose($stream);
            $issues[] = ['file' => $file, 'row' => 1, 'message' => 'The CSV header row is missing.'];

            return [];
        }

        $headerRow[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $headerRow[0]) ?? (string) $headerRow[0];
        $actualHeaders = array_map(static fn (mixed $header): string => trim((string) $header), $headerRow);
        if ($actualHeaders !== $headers) {
            fclose($stream);
            $issues[] = [
                'file' => $file,
                'row' => 1,
                'message' => 'Headers must be exactly: '.implode(',', $headers).'.',
            ];

            return [];
        }

        $rows = [];
        $line = 1;
        while (($values = fgetcsv($stream, null, ',', '"', '')) !== false) {
            $line++;
            if (count($values) === 1 && trim((string) $values[0]) === '') {
                continue;
            }
            if (count($values) !== count($headers)) {
                $issues[] = ['file' => $file, 'row' => $line, 'message' => 'The row has a different number of columns than the header.'];

                continue;
            }

            $rows[] = [
                'line' => $line,
                'values' => array_combine($headers, array_map(static fn (mixed $value): string => trim((string) $value), $values)),
            ];
        }
        fclose($stream);

        return $rows;
    }

    /** @param array<string, string> $row
     *  @return list<string> */
    private function validateRow(string $file, array $row): array
    {
        $required = match ($file) {
            'categories' => ['key', 'name'],
            'models' => ['key', 'category_key', 'name'],
            'variants' => ['key', 'model_key', 'size'],
            'bikes' => ['bid', 'variant_key'],
            default => throw new \InvalidArgumentException("Unsupported CSV type: {$file}"),
        };
        $issues = [];

        foreach ($required as $field) {
            if (($row[$field] ?? '') === '') {
                $issues[] = "{$field} is required.";
            }
        }

        foreach (['in_service', 'self_bookable'] as $booleanField) {
            if (array_key_exists($booleanField, $row) && $row[$booleanField] !== '' && ! in_array(mb_strtolower($row[$booleanField]), ['0', '1', 'true', 'false', 'yes', 'no'], true)) {
                $issues[] = "{$booleanField} must be true or false.";
            }
        }

        foreach (['padding_minutes', 'min_ideal_rider_height', 'max_ideal_rider_height', 'min_extended_rider_height', 'max_extended_rider_height'] as $integerField) {
            if (($row[$integerField] ?? '') !== '' && (! ctype_digit($row[$integerField]) || (int) $row[$integerField] < 0)) {
                $issues[] = "{$integerField} must be a non-negative whole number.";
            }
        }

        if (mb_strlen($row['bid'] ?? '') > 255 || mb_strlen($row['name'] ?? '') > 255 || mb_strlen($row['size'] ?? '') > 255) {
            $issues[] = 'A name, size, or Bike bid is longer than 255 characters.';
        }

        if (isset($row['color_value']) && $row['color_value'] !== '' && ! BrandColor::isValid($row['color_value'])) {
            $issues[] = 'color_value must be a supported color family or six-digit hex color.';
        }

        if (isset($row['interface_color']) && $row['interface_color'] !== '' && ! BrandColor::isValid($row['interface_color'])) {
            $issues[] = 'interface_color must be a supported color family or six-digit hex color.';
        }

        return $issues;
    }

    /** @param array<string, string> $row
     *  @return array<string, mixed> */
    private function attributes(string $file, array $row): array
    {
        $fields = match ($file) {
            'categories' => ['name', 'description', 'interface_color'],
            'models' => ['name', 'manufacturer', 'description', 'padding_minutes'],
            'variants' => ['size', 'color_name', 'color_value', 'min_ideal_rider_height', 'max_ideal_rider_height', 'min_extended_rider_height', 'max_extended_rider_height'],
            'bikes' => ['bid', 'serial_number', 'in_service', 'self_bookable'],
            default => throw new \InvalidArgumentException("Unsupported CSV type: {$file}"),
        };

        $attributes = [];
        foreach ($fields as $field) {
            if (! array_key_exists($field, $row)) {
                continue;
            }
            $value = $row[$field];
            if ($value === '') {
                $attributes[$field] = in_array($field, ['in_service', 'self_bookable'], true) ? true : null;
            } elseif (in_array($field, ['in_service', 'self_bookable'], true)) {
                $attributes[$field] = in_array(mb_strtolower($value), ['1', 'true', 'yes'], true);
            } elseif (in_array($field, ['padding_minutes', 'min_ideal_rider_height', 'max_ideal_rider_height', 'min_extended_rider_height', 'max_extended_rider_height'], true)) {
                $attributes[$field] = (int) $value;
            } else {
                $attributes[$field] = $value;
            }
        }

        if ($file === 'bikes') {
            $attributes['bike_situation_state'] = BikeSituation::Home;
            $attributes['bike_situation_reservation_id'] = null;
        }

        return $attributes;
    }

    /** @param array<string, string> $row
     *  @param array<string, array<string, array{row: array<string, string>, attributes: array<string, mixed>, matched: Model|null, operation: string}>> $plannedRows */
    private function findMatch(string $file, array $row, array $plannedRows): ?Model
    {
        if ($file === 'bikes') {
            return Bike::query()->where('bid', $row['bid'])->first();
        }

        $parentId = null;
        if ($file === 'models') {
            $parentId = $plannedRows['categories'][$row['category_key']]['matched']?->getKey();
        } elseif ($file === 'variants') {
            $parentId = $plannedRows['models'][$row['model_key']]['matched']?->getKey();
        }

        if ($file === 'categories') {
            return BikeCategory::query()->get()->first(fn (BikeCategory $category): bool => $category->name === $row['name']);
        }
        if ($file === 'models' && $parentId !== null) {
            return BikeModel::query()->where('bike_category_id', $parentId)->get()
                ->first(fn (BikeModel $model): bool => $model->name === $row['name']);
        }
        if ($file === 'variants' && $parentId !== null) {
            return BikeModelVariant::query()->where('bike_model_id', $parentId)->get()
                ->first(fn (BikeModelVariant $variant): bool => $variant->size === $row['size']
                    && $variant->color_name === ($row['color_name'] === '' ? null : $row['color_name'])
                    && $variant->color_value === ($row['color_value'] === '' ? null : $row['color_value']));
        }

        return null;
    }

    /** @param array<string, string> $row */
    private function identity(string $file, array $row): string
    {
        $values = match ($file) {
            'categories' => [$row['name'] ?? ''],
            'models' => [$row['category_key'] ?? '', $row['name'] ?? ''],
            'variants' => [$row['model_key'] ?? '', $row['size'] ?? '', $row['color_name'] ?? '', $row['color_value'] ?? ''],
            'bikes' => [$row['bid'] ?? ''],
            default => throw new \InvalidArgumentException("Unsupported CSV type: {$file}"),
        };

        return hash('sha256', json_encode(array_map(static fn (string $value): string => mb_strtolower($value), $values), JSON_THROW_ON_ERROR));
    }
}
