<?php

namespace App\Actions\Reservations;

use App\Actions\Action;
use App\Actions\Concerns\AuthorizesStaff;
use App\Enums\ReservationStage;
use App\Http\Resources\DayPatchResource;
use App\Models\Reservation;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class SetReservationStageAction extends Action
{
    use AuthorizesStaff;

    public string $commandSignature = 'reservations:set-stage {reservation?} {stage?} {--tenant=} {--confirm-out}';

    public function handle(Reservation $reservation, ReservationStage $stage, bool $confirmOutBikesInShop = false): Reservation
    {
        if (! in_array($stage, [ReservationStage::Provisional, ReservationStage::Confirmed, ReservationStage::Cancelled], true)) {
            throw new InvalidArgumentException('Staff may write Provisional, Confirmed, or Cancelled.');
        }

        if ($stage === ReservationStage::Cancelled) {
            return app(CancelAction::class)($reservation, $confirmOutBikesInShop);
        }

        $reservation->stage = $stage;

        if ($stage === ReservationStage::Confirmed) {
            $reservation->expires_at = null;
        }

        $reservation->save();

        return $reservation;
    }

    public function asController(Request $request, Reservation $reservation): DayPatchResource
    {
        $data = $request->validate([
            'stage' => ['required', Rule::enum(ReservationStage::class)],
            'confirm_out_bikes_in_shop' => ['sometimes', 'boolean'],
        ]);

        $reservation = $this->handle(
            $reservation,
            ReservationStage::from($data['stage']),
            (bool) ($data['confirm_out_bikes_in_shop'] ?? false),
        );

        return new DayPatchResource([
            'reservation' => $reservation->refresh()->load(['customer', 'bikeReservations.product.bikeModel']),
        ]);
    }

    public static function routes(Router $router): void
    {
        $router->post('/reservations/{reservation}/stage', static::class)->name('reservations.set-stage');
    }

    public function asCommand(Command $command): int
    {
        $this->initializeTenancyFromCommand($command);
        $this->handle(
            Reservation::query()->findOrFail($command->argument('reservation') ?: \Laravel\Prompts\text('Reservation id')),
            ReservationStage::from((string) ($command->argument('stage') ?: \Laravel\Prompts\text('Stage'))),
            (bool) $command->option('confirm-out'),
        );
        $command->info('Stage updated.');

        return self::SUCCESS;
    }
}
