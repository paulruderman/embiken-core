<?php

namespace Embiken\BikeRentals\Http\Resources;

use Embiken\BikeRentals\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Reservation
 */
class CustomerDisplayTicketResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing([
            'customer',
            'rentalPackage',
            'bikeReservations.product.bikeModel',
            'bikeReservations.bike',
            'waiverVersion',
            'location',
        ]);

        $waiverVersion = $this->waiver_accepted_at !== null
            ? $this->waiverVersion
            : $this->location->currentWaiver();
        $waiverLocale = $this->waiver_accepted_at !== null
            ? $this->waiver_locale
            : (in_array(app()->getLocale(), $this->location->enabled_content_locales, true)
                ? app()->getLocale()
                : $this->location->default_content_locale);
        $waiverContent = $waiverVersion?->getTranslationWithoutFallback('content', $waiverLocale);

        return [
            'id' => $this->id,
            'stage' => $this->stage->value,
            'starts_at' => $this->starts_at->toIso8601String(),
            'ends_at' => $this->ends_at->toIso8601String(),
            'owed_minor_units' => $this->owed,
            'paid_minor_units' => $this->paid,
            'currency' => strtolower($this->currency),
            'package' => $this->rentalPackage === null ? null : [
                'name' => $this->rentalPackage->name,
            ],
            'customer' => [
                'id' => $this->customer_id,
                'name' => $this->customer->name,
                'email' => $this->customer->email,
                'phone' => $this->customer->phone,
            ],
            'waiver_accepted_at' => $this->waiver_accepted_at?->toIso8601String(),
            'waiver' => is_string($waiverContent) && trim($waiverContent) !== '' ? [
                'version_id' => $waiverVersion->id,
                'version' => $waiverVersion->version,
                'locale' => $waiverLocale,
                'content' => $waiverContent,
                'accepted_at' => $this->waiver_accepted_at?->toIso8601String(),
            ] : null,
            'lines' => $this->bikeReservations->map(static function ($line): array {
                $model = $line->product->bikeModel->name;
                $size = $line->product->size;

                return [
                    'id' => $line->id,
                    'product_id' => $line->product_id,
                    'product_label' => trim($model.' '.$size),
                    'bid' => $line->bike?->bid,
                    'status' => $line->status->value,
                ];
            })->all(),
        ];
    }
}
