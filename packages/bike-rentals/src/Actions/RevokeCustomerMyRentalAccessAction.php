<?php

namespace Embiken\BikeRentals\Actions;

use App\Actions\Action;
use App\Actions\Concerns\AuthorizesStaff;
use Embiken\BikeRentals\Models\Customer;
use Illuminate\Console\Command;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\DB;

class RevokeCustomerMyRentalAccessAction extends Action
{
    use AuthorizesStaff;

    public string $commandSignature = 'customers:revoke-myrental-access {customer?} {--tenant=}';

    public function handle(Customer $customer): int
    {
        return DB::transaction(function () use ($customer): int {
            $reservations = $customer->reservations()
                ->whereNotNull('myrental_token')
                ->lockForUpdate()
                ->get();

            foreach ($reservations as $reservation) {
                $reservation->myrental_token = null;
                $reservation->save();
            }

            return $reservations->count();
        });
    }

    public function asController(Request $request, Customer $customer): JsonResponse
    {
        return response()->json(['revoked_reservations' => $this->handle($customer)]);
    }

    public static function routes(Router $router): void
    {
        $router->post('/customers/{customer}/myrental/revoke', static::class)
            ->name('customers.myrental.revoke');
    }

    public function asCommand(Command $command): int
    {
        $this->initializeTenancyFromCommand($command);
        $customerId = $this->requiredStringInput($command, $command->argument('customer'), 'Customer id');
        $customer = Customer::query()->whereKey($customerId)->firstOrFail();
        $revokedReservations = $this->handle($customer);
        $command->info("Revoked access for {$revokedReservations} reservation(s).");

        return self::SUCCESS;
    }
}
