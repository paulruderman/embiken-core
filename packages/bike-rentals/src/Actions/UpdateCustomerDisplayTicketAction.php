<?php

declare(strict_types=1);

namespace Embiken\BikeRentals\Actions;

use App\Actions\Action;
use App\Enums\DeviceSurface;
use App\Models\Device;
use Embiken\BikeRentals\Http\Resources\CustomerDisplayTicketResource;
use Embiken\BikeRentals\Models\Reservation;
use Illuminate\Console\Command;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateCustomerDisplayTicketAction extends Action
{
    public string $commandSignature = 'customer-display:update-ticket {device?} {--tenant=} {--name=} {--email=} {--phone=} {--accept-waiver}';

    public function handle(
        Device $device,
        string $name,
        string $email,
        string $phone,
        bool $waiverAccepted,
        ?int $waiverVersionId,
        ?string $waiverLocale,
    ): Reservation {
        $terminal = match ($device->surface) {
            DeviceSurface::CustomerDisplay => $device->pairedTerminalDevice,
            DeviceSurface::Terminal => $device,
            default => null,
        };

        if (! $terminal instanceof Device
            || $terminal->surface !== DeviceSurface::Terminal
            || $terminal->paired_at === null
            || $terminal->focused_reservation_id === null) {
            throw ValidationException::withMessages([
                'ticket' => 'There is no focused Reservation to update.',
            ]);
        }

        return DB::transaction(function () use ($terminal, $name, $email, $phone, $waiverAccepted, $waiverVersionId, $waiverLocale): Reservation {
            $reservation = Reservation::query()
                ->whereKey($terminal->focused_reservation_id)
                ->where('location_id', $terminal->location_id)
                ->lockForUpdate()
                ->firstOrFail();
            $customer = $reservation->customer()->lockForUpdate()->firstOrFail();
            $customer->fill([
                'name' => trim($name),
                'email' => mb_strtolower(trim($email)),
                'phone' => trim($phone),
            ])->save();

            $currentWaiver = $reservation->location->currentWaiver();
            $waiverContent = $currentWaiver?->getTranslationWithoutFallback('content', (string) $waiverLocale);
            $waiverIsConfigured = is_string($waiverContent) && trim($waiverContent) !== '';

            if ($reservation->waiver_accepted_at === null && $waiverAccepted && $waiverIsConfigured) {
                if ($currentWaiver->id !== $waiverVersionId
                    || ! in_array($waiverLocale, $reservation->location->enabled_content_locales, true)) {
                    throw ValidationException::withMessages([
                        'waiver_version_id' => 'The waiver changed. Review the current text before accepting.',
                    ]);
                }
                $reservation->waiver_accepted_at ??= now('UTC');
                $reservation->waiver_version_id = $currentWaiver->id;
                $reservation->waiver_locale = $waiverLocale;
            }
            $reservation->save();

            return $reservation->refresh()->load([
                'customer',
                'rentalPackage',
                'bikeReservations.product.bikeModel',
                'bikeReservations.bike',
            ]);
        });
    }

    public function asController(Request $request): Reservation
    {
        abort_unless(tenant() !== null, 404);
        $device = $request->attributes->get('device');
        abort_unless($device instanceof Device, 401);

        $data = $request->validate($this->rules());

        return $this->handle(
            $device,
            $data['name'],
            $data['email'],
            $data['phone'],
            $data['waiver_accepted'],
            $data['waiver_version_id'] ?? null,
            $data['waiver_locale'] ?? null,
        );
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'waiver_accepted' => ['required', 'boolean'],
            'waiver_version_id' => ['nullable', 'integer'],
            'waiver_locale' => ['nullable', 'string', 'in:en,es,fr'],
        ];
    }

    public function jsonResponse(Reservation $reservation): JsonResponse
    {
        return response()->json([
            'ticket' => CustomerDisplayTicketResource::make($reservation)->resolve(),
        ]);
    }

    public function asCommand(Command $command): int
    {
        $this->initializeTenancyFromCommand($command);
        $deviceId = $this->requiredStringInput($command, $command->argument('device'), 'Device ID');
        $name = $this->requiredStringInput($command, $command->option('name'), 'Customer name');
        $email = $this->requiredStringInput($command, $command->option('email'), 'Customer email');
        $phone = $this->requiredStringInput($command, $command->option('phone'), 'Customer phone');
        $device = Device::query()->findOrFail($deviceId);

        $this->handle($device, $name, $email, $phone, (bool) $command->option('accept-waiver'), null, null);
        $command->info('Customer Display ticket updated.');

        return self::SUCCESS;
    }
}
