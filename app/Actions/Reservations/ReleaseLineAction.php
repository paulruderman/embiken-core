<?php

namespace App\Actions\Reservations;

use App\Actions\Action;
use App\Models\BikeReservation;
use App\Services\Availability;
use Illuminate\Console\Command;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReleaseLineAction extends Action
{
    public string $commandSignature = 'reservations:release-line {line?} {--tenant=}';

    public function handle(BikeReservation $line): void
    {
        app(Availability::class)->release($line);
    }

    public function asController(Request $request, BikeReservation $line): JsonResponse
    {
        $this->handle($line);

        return response()->json(status: 204);
    }

    public function asCommand(Command $command): int
    {
        $this->initializeTenancyFromCommand($command);
        $this->handle(BikeReservation::query()->findOrFail($command->argument('line') ?: \Laravel\Prompts\text('Line id')));
        $command->info('Released.');

        return self::SUCCESS;
    }
}
