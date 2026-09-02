<?php

namespace App\Actions\Staff;

use App\Actions\Action;
use App\Enums\StaffRole;
use App\Models\Location;
use App\Models\Staff;
use App\Notifications\SetPasswordNotification;
use Filament\Facades\Filament;
use Illuminate\Console\Command;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;

class InviteStaffAction extends Action
{
    public string $commandSignature = 'staff:invite {--tenant=} {--name=} {--email=} {--role=manager}';

    public function handle(Location $location, string $name, string $email, StaffRole $role): Staff
    {
        $staff = Staff::query()->create([
            'location_id' => $location->id,
            'name' => $name,
            'email' => $email,
            'password' => null,
            'role' => $role,
            'is_platform_manager' => false,
        ]);

        $token = Password::broker('staff')->createToken($staff);
        $url = Filament::getPanel('shop')->getResetPasswordUrl($token, $staff);
        $staff->notify(new SetPasswordNotification($url));

        return $staff;
    }

    public function asController(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:staff,email'],
            'role' => ['required', Rule::enum(StaffRole::class)],
        ]);

        $location = Location::query()->firstOrFail();

        return response()->json($this->handle(
            $location,
            $data['name'],
            $data['email'],
            StaffRole::from($data['role']),
        ), 201);
    }

    public function asCommand(Command $command): int
    {
        $this->initializeTenancyFromCommand($command);

        $staff = $this->handle(
            Location::query()->firstOrFail(),
            (string) ($command->option('name') ?: \Laravel\Prompts\text('Name')),
            (string) ($command->option('email') ?: \Laravel\Prompts\text('Email')),
            StaffRole::from((string) $command->option('role')),
        );

        $command->info("Invited staff {$staff->id}");

        return self::SUCCESS;
    }
}
