<?php

namespace App\Services;

use App\Enums\BikeAssignmentPolicy;
use App\Enums\ReturnSituation;
use App\Enums\Weekday;
use App\Models\Location;
use App\Models\LocationClosedDate;
use App\Models\LocationHour;
use Embiken\BikeRentals\Models\RentalPackage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use JsonException;

class LocationMetadataImport
{
    /** @return array<string, mixed> */
    public function template(): array
    {
        $hours = [];

        foreach (Weekday::cases() as $weekday) {
            $hours[mb_strtolower($weekday->name)] = [[
                'opens_at' => '09:00',
                'closes_at' => '18:00',
                'closes_next_day' => false,
            ]];
        }

        return [
            'shop' => [
                'name' => 'Example Bike Shop',
                'customer_contact_email' => 'rentals@example-bike-shop.example',
                'timezone' => 'America/New_York',
                'currency' => 'usd',
                'minimum_turnaround_buffer_minutes' => 10,
                'bike_assignment_policy' => BikeAssignmentPolicy::Terminal->value,
                'return_situation' => ReturnSituation::Home->value,
                'hours' => $hours,
                'closed_dates' => [],
            ],
        ];
    }

    public function templateJson(): string
    {
        return json_encode($this->template(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n";
    }

    /**
     * @return array{
     *     shop: array{name: string, customer_contact_email?: string|null, timezone: string, currency: string, minimum_turnaround_buffer_minutes: int, bike_assignment_policy: string, return_situation: string, hours: array<string, list<array{opens_at: string, closes_at: string, closes_next_day: bool}>>, closed_dates: list<string>}
     * }
     */
    public function parse(string $json): array
    {
        try {
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw ValidationException::withMessages([
                'file' => 'The JSON file is invalid: '.$exception->getMessage(),
            ]);
        }

        if (! is_array($data) || array_is_list($data) || array_diff(array_keys($data), ['shop']) !== []) {
            throw ValidationException::withMessages(['file' => 'The JSON root must be an object.']);
        }

        $validator = Validator::make($data, $this->rules());

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        /** @var array{shop: array{name: string, customer_contact_email?: string|null, timezone: string, currency: string, minimum_turnaround_buffer_minutes: int|string, bike_assignment_policy: string, return_situation: string, hours: array<string, list<array{opens_at: string, closes_at: string, closes_next_day: bool|string}>>, closed_dates: list<string>}} $validated */
        $validated = $validator->validated();
        $issues = $this->intervalIssues($validated['shop']['hours']);

        if ($issues !== []) {
            throw ValidationException::withMessages($issues);
        }

        $validated['shop']['currency'] = mb_strtolower($validated['shop']['currency']);
        $validated['shop']['minimum_turnaround_buffer_minutes'] = (int) $validated['shop']['minimum_turnaround_buffer_minutes'];
        $validated['shop']['hours'] = array_map(
            fn (array $intervals): array => array_map(fn (array $interval): array => [
                'opens_at' => $interval['opens_at'],
                'closes_at' => $interval['closes_at'],
                'closes_next_day' => filter_var($interval['closes_next_day'], FILTER_VALIDATE_BOOL),
            ], $intervals),
            $validated['shop']['hours'],
        );

        return $validated;
    }

    /** @return array<string, mixed> */
    public function preview(string $json): array
    {
        try {
            $metadata = $this->parse($json);
        } catch (ValidationException $exception) {
            $issues = [];

            foreach ($exception->errors() as $field => $messages) {
                foreach ($messages as $message) {
                    $issues[] = ['file' => 'shop.json', 'row' => null, 'message' => "{$field}: {$message}"];
                }
            }

            return [
                'summary' => $this->emptySummary(),
                'issues' => $issues,
                'can_apply' => false,
                'valid_rows' => 0,
            ];
        }

        $location = Location::query()->firstOrFail();
        $issues = [];

        if (mb_strtolower($location->currency) !== $metadata['shop']['currency']
            && RentalPackage::query()->exists()) {
            $issues[] = [
                'file' => 'shop.json',
                'row' => null,
                'message' => 'Currency cannot be changed while rental packages exist.',
            ];
        }

        $hourCount = array_sum(array_map('count', $metadata['shop']['hours']));
        $closedDateCount = count($metadata['shop']['closed_dates']);

        return [
            'summary' => [
                'shop' => ['create' => 0, 'update' => 1, 'skip' => 0, 'conflict' => 0, 'invalid' => 0],
                'hours' => ['create' => $hourCount, 'update' => $location->hours()->count(), 'skip' => 0, 'conflict' => 0, 'invalid' => 0],
                'closed_dates' => ['create' => $closedDateCount, 'update' => $location->closedDates()->count(), 'skip' => 0, 'conflict' => 0, 'invalid' => 0],
            ],
            'issues' => $issues,
            'can_apply' => $issues === [],
            'valid_rows' => 1 + $hourCount + $closedDateCount,
        ];
    }

    /** @return array<string, mixed> */
    public function apply(string $json): array
    {
        $report = $this->preview($json);

        if (! $report['can_apply']) {
            return [...$report, 'applied' => false];
        }

        $metadata = $this->parse($json);

        return DB::transaction(function () use ($metadata, $report): array {
            $location = Location::query()->lockForUpdate()->firstOrFail();

            if (mb_strtolower($location->currency) !== $metadata['shop']['currency']
                && RentalPackage::query()->exists()) {
                return [
                    ...$report,
                    'issues' => [[
                        'file' => 'shop.json',
                        'row' => null,
                        'message' => 'Currency cannot be changed while rental packages exist.',
                    ]],
                    'can_apply' => false,
                    'applied' => false,
                ];
            }

            $shop = $metadata['shop'];
            $location->fill([
                'name' => $shop['name'],
                'timezone' => $shop['timezone'],
                'currency' => $shop['currency'],
                'minimum_turnaround_buffer_minutes' => $shop['minimum_turnaround_buffer_minutes'],
                'bike_assignment_policy' => $shop['bike_assignment_policy'],
                'return_situation' => $shop['return_situation'],
                ...(array_key_exists('customer_contact_email', $shop)
                    ? ['customer_contact_email' => $shop['customer_contact_email']]
                    : []),
            ])->save();

            $location->hours()->delete();
            foreach (Weekday::cases() as $weekday) {
                foreach ($shop['hours'][mb_strtolower($weekday->name)] as $interval) {
                    LocationHour::query()->create([
                        'location_id' => $location->id,
                        'weekday' => $weekday,
                        ...$interval,
                    ]);
                }
            }

            $location->closedDates()->delete();
            foreach ($shop['closed_dates'] as $date) {
                LocationClosedDate::query()->create([
                    'location_id' => $location->id,
                    'date' => $date,
                ]);
            }

            return [...$report, 'applied' => true];
        }, attempts: 3);
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        $weekdays = array_map(fn (Weekday $weekday): string => mb_strtolower($weekday->name), Weekday::cases());
        $allowedHoursKeys = implode(',', $weekdays);
        $rules = [
            'shop' => ['required', 'array:name,customer_contact_email,timezone,currency,minimum_turnaround_buffer_minutes,bike_assignment_policy,return_situation,hours,closed_dates'],
            'shop.name' => ['required', 'string', 'max:255'],
            'shop.customer_contact_email' => ['sometimes', 'nullable', 'email', 'max:254'],
            'shop.timezone' => ['required', 'timezone'],
            'shop.currency' => ['required', 'string', 'size:3', 'alpha'],
            'shop.minimum_turnaround_buffer_minutes' => ['required', 'integer', 'min:0', 'max:1440'],
            'shop.bike_assignment_policy' => ['required', 'string', 'in:'.implode(',', array_column(BikeAssignmentPolicy::cases(), 'value'))],
            'shop.return_situation' => ['required', 'string', 'in:'.implode(',', array_column(ReturnSituation::cases(), 'value'))],
            'shop.hours' => ['required', "array:{$allowedHoursKeys}"],
            'shop.closed_dates' => ['present', 'array'],
            'shop.closed_dates.*' => ['required', 'date_format:Y-m-d', 'distinct'],
            'shop.hours.*' => ['present', 'array'],
            'shop.hours.*.*' => ['required', 'array:opens_at,closes_at,closes_next_day'],
            'shop.hours.*.*.opens_at' => ['required', 'date_format:H:i'],
            'shop.hours.*.*.closes_at' => ['required', 'date_format:H:i'],
            'shop.hours.*.*.closes_next_day' => ['required', 'boolean'],
        ];

        foreach ($weekdays as $weekday) {
            $rules["shop.hours.{$weekday}"] = ['present', 'array'];
        }

        return $rules;
    }

    /** @param array<string, list<array{opens_at: string, closes_at: string, closes_next_day: bool|string}>> $hours
     *  @return array<string, list<string>> */
    private function intervalIssues(array $hours): array
    {
        $week = 7 * 24 * 60;
        $intervals = [];
        $issues = [];

        foreach (Weekday::cases() as $weekday) {
            $key = mb_strtolower($weekday->name);
            $dayStart = $weekday->value * 24 * 60;

            foreach ($hours[$key] as $index => $interval) {
                [$openHour, $openMinute] = array_map('intval', explode(':', $interval['opens_at']));
                [$closeHour, $closeMinute] = array_map('intval', explode(':', $interval['closes_at']));
                $startsAt = $dayStart + ($openHour * 60) + $openMinute;
                $endsAt = $dayStart + ($closeHour * 60) + $closeMinute;

                if (filter_var($interval['closes_next_day'], FILTER_VALIDATE_BOOL)) {
                    $endsAt += 24 * 60;
                }

                if ($endsAt <= $startsAt) {
                    $issues["shop.hours.{$key}.{$index}.closes_at"][] = 'The close time must be after the open time.';

                    continue;
                }

                $intervals[] = ['key' => "{$key}.{$index}", 'start' => $startsAt, 'end' => $endsAt];
            }
        }

        foreach ($intervals as $index => $interval) {
            foreach (array_slice($intervals, $index + 1) as $other) {
                foreach ([-$week, 0, $week] as $shift) {
                    $shiftedStart = $other['start'] + $shift;
                    $shiftedEnd = $other['end'] + $shift;

                    if ($interval['start'] < $shiftedEnd && $shiftedStart < $interval['end']) {
                        $issues['shop.hours'][] = "The intervals {$interval['key']} and {$other['key']} overlap.";
                        break 2;
                    }
                }
            }
        }

        return $issues;
    }

    /** @return array<string, array{create: int, update: int, skip: int, conflict: int, invalid: int}> */
    private function emptySummary(): array
    {
        return array_fill_keys(['shop', 'hours', 'closed_dates'], [
            'create' => 0,
            'update' => 0,
            'skip' => 0,
            'conflict' => 0,
            'invalid' => 0,
        ]);
    }
}
