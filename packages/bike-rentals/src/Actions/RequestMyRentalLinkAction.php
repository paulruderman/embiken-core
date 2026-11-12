<?php

namespace Embiken\BikeRentals\Actions;

use App\Actions\Action;
use Embiken\BikeRentals\Enums\ReservationStage;
use Embiken\BikeRentals\Mail\MyRentalReservationNotice;
use Embiken\BikeRentals\Models\Reservation;
use Embiken\BikeRentals\Services\MyRentalLink;
use Illuminate\Console\Command;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class RequestMyRentalLinkAction extends Action
{
    public string $commandSignature = 'reservations:request-myrental-link {email?} {--tenant=}';

    public function handle(string $email, MyRentalLink $links): void
    {
        $normalizedEmail = Str::lower(trim($email));

        $reservations = Reservation::query()
            ->with(['customer', 'location'])
            ->whereNotNull('myrental_token')
            ->where('ends_at', '>=', now()->subDays(3))
            ->whereNotIn('stage', [ReservationStage::Provisional, ReservationStage::Cancelled])
            ->whereHas('customer', fn ($query) => $query->whereRaw('LOWER(email) = ?', [$normalizedEmail]))
            ->get();

        foreach ($reservations as $reservation) {
            $url = $links->for($reservation);

            if ($url !== null) {
                Mail::to($reservation->customer->email)->queue(
                    new MyRentalReservationNotice($reservation, $url, 'Your reservation link'),
                );
            }
        }
    }

    /** @return array<string, string|array<mixed>> */
    public function rules(): array
    {
        return ['email' => ['required', 'email', 'max:255']];
    }

    public function asController(Request $request): JsonResponse
    {
        $data = $request->validate($this->rules());
        $this->handle($data['email'], app(MyRentalLink::class));

        return response()->json([
            'message' => 'If an eligible reservation matches that email, its link will be sent shortly.',
        ], 202);
    }

    public static function routes(Router $router): void
    {
        $router->post('/myrental/link', static::class)
            ->middleware('throttle:5,1')
            ->name('myrental.link.request');
    }

    public function asCommand(Command $command): int
    {
        $this->initializeTenancyFromCommand($command);
        $email = $this->requiredStringInput($command, $command->argument('email'), 'Email');
        $this->handle($email, app(MyRentalLink::class));
        $command->info('If an eligible reservation matches that email, its link will be sent shortly.');

        return self::SUCCESS;
    }
}
