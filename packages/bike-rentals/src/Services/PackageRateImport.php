<?php

declare(strict_types=1);

namespace Embiken\BikeRentals\Services;

use App\Models\Location;
use Embiken\BikeFleet\Models\BikeCategory;
use Embiken\BikeFleet\Models\BikeModel;
use Embiken\BikeFleet\Models\BikeModelVariant;
use Embiken\BikeRentals\Enums\ConfirmThreshold;
use Embiken\BikeRentals\Enums\PackageMeter;
use Embiken\BikeRentals\Models\RentalPackage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PackageRateImport
{
    private const array FILES = [
        'packages' => ['key', 'name', 'tagline', 'blurb', 'description', 'meter', 'confirm_threshold', 'deposit_minor_units', 'deposit_percent', 'min_duration_minutes', 'max_duration_minutes', 'book_visible', 'sort_order'],
        'package_rates' => ['package_key', 'category_name', 'model_name', 'variant_size', 'variant_color_name', 'variant_color_value', 'rate_minor_units'],
    ];

    /** @return array<string, string> */
    public function templates(): array
    {
        return array_map(static fn (array $headers): string => implode(',', $headers)."\n", self::FILES);
    }

    /** @param array<string, string> $files
     *  @return array{summary: array<string, array{create: int, update: int, skip: int, conflict: int, invalid: int}>, issues: list<array{file: string, row: int|null, message: string}>, can_apply: bool, valid_rows: int} */
    public function preview(array $files, string $duplicateStrategy, string $partialStrategy): array
    {
        return $this->analyze($files, $duplicateStrategy, $partialStrategy)['report'];
    }

    /** @param array<string, string> $files
     *  @return array{summary: array<string, array{create: int, update: int, skip: int, conflict: int, invalid: int}>, issues: list<array{file: string, row: int|null, message: string}>, can_apply: bool, valid_rows: int, applied: bool} */
    public function apply(array $files, string $duplicateStrategy, string $partialStrategy): array
    {
        return DB::transaction(function () use ($files, $duplicateStrategy, $partialStrategy): array {
            $analysis = $this->analyze($files, $duplicateStrategy, $partialStrategy);
            $report = $analysis['report'];
            if (! $report['can_apply']) {
                return [...$report, 'applied' => false];
            }

            $location = Location::query()->firstOrFail();
            $packages = [];
            foreach ($analysis['rows']['packages'] as $entry) {
                $package = $entry['matched'];
                if (in_array($entry['operation'], ['create', 'update'], true)) {
                    $attributes = $entry['attributes'];
                    $attributes['location_id'] = $location->getKey();
                    if ($entry['operation'] === 'create') {
                        $package = RentalPackage::query()->create($attributes);
                    } else {
                        $package->fill($attributes)->save();
                    }
                }
                if ($package instanceof RentalPackage) {
                    $packages[$entry['row']['key']] = $package;
                }
            }

            foreach ($analysis['rows']['package_rates'] as $entry) {
                if (! in_array($entry['operation'], ['create', 'update'], true)) {
                    continue;
                }
                $row = $entry['row'];
                $package = $packages[$row['package_key']] ?? null;
                $variant = $entry['variant'];
                if (! $package instanceof RentalPackage || ! $variant instanceof BikeModelVariant) {
                    continue;
                }
                $pivot = DB::table('rental_package_product')
                    ->where('rental_package_id', $package->getKey())
                    ->where('product_id', $variant->getKey());
                if ($pivot->exists()) {
                    $package->variants()->updateExistingPivot($variant->getKey(), $entry['attributes']);
                } else {
                    $package->variants()->attach($variant->getKey(), $entry['attributes']);
                }
            }

            return [...$report, 'applied' => true];
        }, attempts: 3);
    }

    /** @param array<string, string> $files
     *  @return array{report: array{summary: array<string, array{create: int, update: int, skip: int, conflict: int, invalid: int}>, issues: list<array{file: string, row: int|null, message: string}>, can_apply: bool, valid_rows: int}, rows: array{packages: array<string, array{row: array<string, string>, attributes: array<string, mixed>, matched: ?RentalPackage, operation: string}>, package_rates: list<array{row: array<string, string>, attributes: array<string, mixed>, variant: ?BikeModelVariant, operation: string}>}} */
    private function analyze(array $files, string $duplicateStrategy, string $partialStrategy): array
    {
        $summary = [
            'packages' => ['create' => 0, 'update' => 0, 'skip' => 0, 'conflict' => 0, 'invalid' => 0],
            'package_rates' => ['create' => 0, 'update' => 0, 'skip' => 0, 'conflict' => 0, 'invalid' => 0],
        ];
        $issues = [];
        $packageRows = [];
        $packageEntries = [];
        $seenPackageNames = [];
        foreach ($this->parse('packages', self::FILES['packages'], $files['packages'] ?? null, $issues) as $record) {
            $row = $record['values'];
            $errors = $this->validatePackage($row);
            $key = $row['key'];
            $planKey = $key;
            if (isset($packageRows[$key])) {
                $errors[] = "Duplicate key '{$key}' in this file.";
                $planKey = $key.'#row'.$record['line'];
            }
            $nameIdentity = mb_strtolower($row['name']);
            if (isset($seenPackageNames[$nameIdentity])) {
                $errors[] = 'Another row in this file has the same package name.';
            }
            $seenPackageNames[$nameIdentity] = true;
            $matched = $errors === [] ? RentalPackage::query()->get()->first(fn (RentalPackage $package): bool => $package->name === $row['name']) : null;
            $classification = $this->classify($matched, $errors, $duplicateStrategy);
            $operation = $classification['operation'];
            $summary['packages'][$this->countKey($operation)]++;
            $this->appendIssues($issues, 'packages', $record['line'], $classification['issues']);
            $entry = ['row' => $row, 'attributes' => $this->packageAttributes($row), 'matched' => $matched, 'operation' => $operation];
            $packageEntries[$planKey] = $entry;
            if (! isset($packageRows[$key])) {
                $packageRows[$key] = $entry;
            }
        }

        $rateRows = [];
        $seenRates = [];
        foreach ($this->parse('package_rates', self::FILES['package_rates'], $files['package_rates'] ?? null, $issues) as $record) {
            $row = $record['values'];
            $errors = [];
            if (! isset($packageRows[$row['package_key']]) || $packageRows[$row['package_key']]['operation'] === 'invalid') {
                $errors[] = 'The referenced package_key was not found or is invalid.';
            } elseif ($packageRows[$row['package_key']]['row']['meter'] === PackageMeter::None->value && $row['rate_minor_units'] !== '0') {
                $errors[] = 'Packages with meter none must have a rate of 0.';
            }
            if ($row['rate_minor_units'] === '' || ! ctype_digit($row['rate_minor_units'])) {
                $errors[] = 'rate_minor_units must be a non-negative whole number.';
            }
            $variant = $this->findVariant($row);
            if (! $variant instanceof BikeModelVariant) {
                $errors[] = 'The referenced catalog variant was not found.';
            }
            $identity = implode('|', [$row['package_key'], $row['category_name'], $row['model_name'], $row['variant_size'], $row['variant_color_name'], $row['variant_color_value']]);
            if (isset($seenRates[$identity])) {
                $errors[] = 'Another row in this file describes the same package rate.';
            }
            $seenRates[$identity] = true;
            $existing = $variant instanceof BikeModelVariant && isset($packageRows[$row['package_key']]['matched'])
                ? DB::table('rental_package_product')->where('rental_package_id', $packageRows[$row['package_key']]['matched']->getKey())->where('product_id', $variant->getKey())->exists()
                : false;
            $classification = $this->classify($existing, $errors, $duplicateStrategy);
            $operation = $classification['operation'];
            $summary['package_rates'][$this->countKey($operation)]++;
            $this->appendIssues($issues, 'package_rates', $record['line'], $classification['issues']);
            $rateRows[] = ['row' => $row, 'attributes' => ['rate_minor_units' => (int) $row['rate_minor_units']], 'variant' => $variant, 'operation' => $operation];
        }

        $validRows = $summary['packages']['create'] + $summary['packages']['update'] + $summary['packages']['skip']
            + $summary['package_rates']['create'] + $summary['package_rates']['update'] + $summary['package_rates']['skip'];
        $hasIssues = $issues !== [];

        return [
            'report' => ['summary' => $summary, 'issues' => $issues, 'can_apply' => $validRows > 0 && ($partialStrategy === 'partial' || ! $hasIssues), 'valid_rows' => $validRows],
            'rows' => ['packages' => $packageEntries, 'package_rates' => $rateRows],
        ];
    }

    /** @param array<string, string> $row
     *  @return list<string> */
    private function validatePackage(array $row): array
    {
        $issues = [];
        foreach (['key', 'name', 'meter', 'confirm_threshold'] as $required) {
            if ($row[$required] === '') {
                $issues[] = "{$required} is required.";
            }
        }
        if (! in_array($row['meter'], array_column(PackageMeter::cases(), 'value'), true)) {
            $issues[] = 'meter must be none, per_hour, per_line, or per_calendar_day.';
        }
        if (! in_array($row['confirm_threshold'], array_column(ConfirmThreshold::cases(), 'value'), true)) {
            $issues[] = 'confirm_threshold must be none, deposit, or full.';
        }
        foreach (['deposit_minor_units', 'deposit_percent', 'min_duration_minutes', 'max_duration_minutes', 'sort_order'] as $field) {
            if ($row[$field] !== '' && ! ctype_digit($row[$field])) {
                $issues[] = "{$field} must be a non-negative whole number.";
            }
        }
        if ($row['min_duration_minutes'] !== '' && $row['max_duration_minutes'] !== '' && (int) $row['max_duration_minutes'] < (int) $row['min_duration_minutes']) {
            $issues[] = 'max_duration_minutes must be greater than or equal to min_duration_minutes.';
        }
        if ($row['deposit_percent'] !== '' && (int) $row['deposit_percent'] > 100) {
            $issues[] = 'deposit_percent must be between 0 and 100.';
        }
        if ($row['confirm_threshold'] === ConfirmThreshold::Deposit->value && (($row['deposit_minor_units'] === '') === ($row['deposit_percent'] === ''))) {
            $issues[] = 'A deposit package must specify exactly one of deposit_minor_units or deposit_percent.';
        }
        if ($row['confirm_threshold'] !== ConfirmThreshold::Deposit->value && ($row['deposit_minor_units'] !== '' || $row['deposit_percent'] !== '')) {
            $issues[] = 'Deposit fields are only allowed when confirm_threshold is deposit.';
        }
        if ($row['book_visible'] !== '' && ! in_array(mb_strtolower($row['book_visible']), ['0', '1', 'true', 'false', 'yes', 'no'], true)) {
            $issues[] = 'book_visible must be true or false.';
        }
        if ($row['name'] !== '' && mb_strlen($row['name']) > 255) {
            $issues[] = 'name is longer than 255 characters.';
        }

        return $issues;
    }

    /** @param array<string, string> $row
     *  @return array<string, mixed> */
    private function packageAttributes(array $row): array
    {
        return [
            'name' => $row['name'],
            'tagline' => $row['tagline'] === '' ? null : $row['tagline'],
            'blurb' => $row['blurb'] === '' ? null : $row['blurb'],
            'description' => $row['description'] === '' ? null : $row['description'],
            'meter' => $row['meter'],
            'confirm_threshold' => $row['confirm_threshold'],
            'deposit_minor_units' => $row['deposit_minor_units'] === '' ? null : (int) $row['deposit_minor_units'],
            'deposit_percent' => $row['deposit_percent'] === '' ? null : (int) $row['deposit_percent'],
            'min_duration_minutes' => $row['min_duration_minutes'] === '' ? null : (int) $row['min_duration_minutes'],
            'max_duration_minutes' => $row['max_duration_minutes'] === '' ? null : (int) $row['max_duration_minutes'],
            'book_visible' => $row['book_visible'] === '' || in_array(mb_strtolower($row['book_visible']), ['1', 'true', 'yes'], true),
            'sort_order' => $row['sort_order'] === '' ? 0 : (int) $row['sort_order'],
        ];
    }

    /** @param array<string, string> $row */
    private function findVariant(array $row): ?BikeModelVariant
    {
        $category = BikeCategory::query()->get()->first(fn (BikeCategory $record): bool => $record->name === $row['category_name']);
        if (! $category instanceof BikeCategory) {
            return null;
        }
        $model = BikeModel::query()->where('bike_category_id', $category->getKey())->get()
            ->first(fn (BikeModel $record): bool => $record->name === $row['model_name']);
        if (! $model instanceof BikeModel) {
            return null;
        }

        return BikeModelVariant::query()->where('bike_model_id', $model->getKey())->get()
            ->first(fn (BikeModelVariant $record): bool => $record->size === $row['variant_size']
                && $record->color_name === ($row['variant_color_name'] === '' ? null : $row['variant_color_name'])
                && $record->color_value === ($row['variant_color_value'] === '' ? null : $row['variant_color_value']));
    }

    /** @param list<string> $headers
     *  @param list<array{file: string, row: int|null, message: string}> $issues
     *  @return list<array{line: int, values: array<string, string>}> */
    private function parse(string $file, array $headers, ?string $contents, array &$issues): array
    {
        if ($contents === null || trim($contents) === '') {
            $issues[] = ['file' => $file, 'row' => null, 'message' => 'A non-empty CSV file is required.'];

            return [];
        }
        $stream = fopen('php://temp', 'r+');
        if ($stream === false) {
            $issues[] = ['file' => $file, 'row' => null, 'message' => 'The CSV could not be read.'];

            return [];
        }
        fwrite($stream, $contents);
        rewind($stream);
        $actual = fgetcsv($stream, null, ',', '"', '');
        if (! is_array($actual)) {
            fclose($stream);
            $issues[] = ['file' => $file, 'row' => 1, 'message' => 'The CSV header row is missing.'];

            return [];
        }
        $actual[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $actual[0]) ?? (string) $actual[0];
        if (array_map(static fn (mixed $value): string => trim((string) $value), $actual) !== $headers) {
            fclose($stream);
            $issues[] = ['file' => $file, 'row' => 1, 'message' => 'Headers must be exactly: '.implode(',', $headers).'.'];

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
            $rows[] = ['line' => $line, 'values' => array_combine($headers, array_map(static fn (mixed $value): string => trim((string) $value), $values))];
        }
        fclose($stream);

        return $rows;
    }

    /** @param list<string> $errors
     *  @return array{operation: string, issues: list<string>} */
    private function classify(Model|bool|null $matched, array $errors, string $duplicateStrategy): array
    {
        if ($errors !== []) {
            return ['operation' => 'invalid', 'issues' => $errors];
        }
        $exists = $matched instanceof Model || $matched === true;
        $operation = $exists ? match ($duplicateStrategy) {
            'update' => 'update', 'skip' => 'skip', default => 'conflict'
        } : 'create';
        if ($operation === 'conflict') {
            return ['operation' => $operation, 'issues' => ['A matching record already exists.']];
        }

        return ['operation' => $operation, 'issues' => []];
    }

    /** @return 'invalid'|'conflict'|'skip'|'update'|'create' */
    private function countKey(string $operation): string
    {
        return match ($operation) {
            'invalid' => 'invalid', 'conflict' => 'conflict', 'skip' => 'skip', 'update' => 'update', default => 'create',
        };
    }

    /** @param list<array{file: string, row: int|null, message: string}> $issues
     *  @param list<string> $messages */
    private function appendIssues(array &$issues, string $file, int $line, array $messages): void
    {
        foreach ($messages as $message) {
            $issues[] = ['file' => $file, 'row' => $line, 'message' => $message];
        }
    }
}
