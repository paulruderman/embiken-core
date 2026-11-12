<?php

namespace App\Filament\Shop\Resources\Locations\Pages;

use App\Filament\Shop\Resources\Locations\LocationResource;
use App\Models\Location;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class EditLocation extends EditRecord
{
    protected static string $resource = LocationResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (! $this->record instanceof Location) {
            return $data;
        }

        $translations = $this->record->currentWaiver()?->getTranslations('content') ?? [];

        foreach ($this->record->enabled_content_locales as $locale) {
            $data['waiver_text_'.$locale] = $translations[$locale] ?? '';
        }

        return $data;
    }

    /** @param array<string, mixed> $data */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if (! $record instanceof Location) {
            throw new \LogicException('The Location resource must edit a Location record.');
        }
        $translations = [];
        $enabledLocales = is_array($data['enabled_content_locales'] ?? null)
            ? $data['enabled_content_locales']
            : $record->enabled_content_locales;
        foreach ($enabledLocales as $locale) {
            $translations[$locale] = trim((string) Arr::pull($data, 'waiver_text_'.$locale, ''));
        }

        return DB::transaction(function () use ($record, $data, $translations): Location {
            $record->update($data);
            ksort($translations);
            $canonical = json_encode($translations, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            $hash = hash('sha256', $canonical);
            $current = $record->currentWaiver();

            if ($current?->content_sha256 !== $hash) {
                $record->waivers()->create([
                    'version' => ((int) $record->waivers()->max('version')) + 1,
                    'content' => $translations,
                    'content_sha256' => $hash,
                ]);
            }

            return $record;
        });
    }
}
