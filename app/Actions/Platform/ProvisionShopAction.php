<?php

namespace App\Actions\Platform;

use App\Actions\Action;
use App\Actions\Staff\InviteStaffAction;
use App\Enums\BikeAssignmentPolicy;
use App\Enums\ReturnSituation;
use App\Enums\StaffRole;
use App\Models\Location;
use App\Models\Staff;
use Illuminate\Console\Command;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProvisionShopAction extends Action
{
    public string $commandSignature = 'tenants:provision-shop {--tenant=} {--shop-name=} {--timezone=America/New_York} {--currency=usd} {--manager-name=} {--manager-email=}';

    public function handle(
        string $shopName,
        string $timezone,
        string $currency,
        string $managerName,
        string $managerEmail,
    ): Location {
        $location = Location::query()->first();

        if ($location === null) {
            $location = Location::query()->create([
                'name' => $shopName,
                'timezone' => $timezone,
                'currency' => strtolower($currency),
                'minimum_turnaround_buffer_minutes' => 10,
                'bike_assignment_policy' => BikeAssignmentPolicy::Terminal,
                'return_situation' => ReturnSituation::Home,
            ]);
        }

        if (! Staff::query()->where('is_platform_manager', true)->exists()) {
            Staff::query()->create([
                'location_id' => $location->id,
                'name' => 'Platform',
                'email' => 'platform@'.$location->id.'.embiken.internal',
                'password' => null,
                'role' => StaffRole::Manager,
                'is_platform_manager' => true,
            ]);
        }

        app(InviteStaffAction::class)(
            location: $location,
            name: $managerName,
            email: $managerEmail,
            role: StaffRole::Manager,
        );

        return $location;
    }

    public function asController(Request $request): JsonResponse
    {
        $data = $request->validate([
            'shop_name' => ['required', 'string'],
            'timezone' => ['required', 'timezone'],
            'currency' => ['required', 'string', 'size:3'],
            'manager_name' => ['required', 'string'],
            'manager_email' => ['required', 'email'],
        ]);

        return response()->json($this->handle(
            $data['shop_name'],
            $data['timezone'],
            $data['currency'],
            $data['manager_name'],
            $data['manager_email'],
        ));
    }

    public function asCommand(Command $command): int
    {
        $this->initializeTenancyFromCommand($command);

        $this->handle(
            (string) ($command->option('shop-name') ?: \Laravel\Prompts\text('Shop name')),
            (string) $command->option('timezone'),
            (string) $command->option('currency'),
            (string) ($command->option('manager-name') ?: \Laravel\Prompts\text('Manager name')),
            (string) ($command->option('manager-email') ?: \Laravel\Prompts\text('Manager email')),
        );

        $command->info('Shop provisioned.');

        return self::SUCCESS;
    }
}
