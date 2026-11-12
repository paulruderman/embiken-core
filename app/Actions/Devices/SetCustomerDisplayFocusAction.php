<?php

declare(strict_types=1);

namespace App\Actions\Devices;

use App\Actions\Action;
use App\Enums\DeviceSurface;
use App\Models\Device;
use App\Support\CustomerDisplayTicketBroadcaster;
use Embiken\BikeRentals\Http\Resources\CustomerDisplayTicketResource;
use Embiken\BikeRentals\Models\Reservation;
use Illuminate\Console\Command;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SetCustomerDisplayFocusAction extends Action
{
    public string $commandSignature = 'device:focus-reservation {device?} {reservation?} {--tenant=}';

    /** @return array{focused_reservation_id: int|null, ticket: array<string, mixed>|null} */
    public function handle(Device $terminal, ?int $reservationId): array
    {
        if ($terminal->surface !== DeviceSurface::Terminal || $terminal->paired_at === null) {
            throw ValidationException::withMessages([
                'device' => 'Choose a paired Terminal Device.',
            ]);
        }

        $reservation = $reservationId === null
            ? null
            : Reservation::query()
                ->whereKey($reservationId)
                ->where('location_id', $terminal->location_id)
                ->with(['customer', 'rentalPackage', 'bikeReservations.product.bikeModel', 'bikeReservations.bike'])
                ->firstOrFail();

        DB::transaction(function () use ($terminal, $reservationId): void {
            $lockedTerminal = Device::query()->whereKey($terminal->id)->lockForUpdate()->firstOrFail();
            $lockedTerminal->focused_reservation_id = $reservationId;
            $lockedTerminal->save();
        });

        app(CustomerDisplayTicketBroadcaster::class)->broadcastForTerminal($terminal->fresh(), $reservation);

        return [
            'focused_reservation_id' => $reservation?->id,
            'ticket' => $reservation === null
                ? null
                : CustomerDisplayTicketResource::make($reservation)->resolve(),
        ];
    }

    /** @return array{focused_reservation_id: int|null, ticket: array<string, mixed>|null} */
    public function asController(Request $request): array
    {
        abort_unless(tenant() !== null, 404);
        $terminal = $request->attributes->get('device');
        $staff = Auth::guard('staff')->user();
        abort_unless(
            $terminal instanceof Device
                && $terminal->surface === DeviceSurface::Terminal
                && $staff?->location_id === $terminal->location_id,
            403,
        );

        $data = $request->validate([
            'reservation_id' => ['nullable', 'integer'],
        ]);
        $reservationId = isset($data['reservation_id']) ? (int) $data['reservation_id'] : null;

        return $this->handle($terminal, $reservationId);
    }

    /**
     * @param  array{focused_reservation_id: int|null, ticket: array<string, mixed>|null}  $result
     */
    public function jsonResponse(array $result): JsonResponse
    {
        return response()->json($result);
    }

    public function asCommand(Command $command): int
    {
        $this->initializeTenancyFromCommand($command);
        $deviceId = $this->requiredStringInput($command, $command->argument('device'), 'Device ID');
        $device = Device::query()->findOrFail($deviceId);
        $reservationId = $this->optionalStringValue($command->argument('reservation'), 'reservation');

        $result = $this->handle($device, $reservationId === null ? null : (int) $reservationId);
        $command->info($result['focused_reservation_id'] === null
            ? 'Customer Display focus cleared.'
            : "Customer Display focused on Reservation {$result['focused_reservation_id']}.");

        return self::SUCCESS;
    }
}
