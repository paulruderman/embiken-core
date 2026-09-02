<?php

namespace App\Actions\Platform;

use App\Actions\Action;
use App\Models\User;
use App\Notifications\SetPasswordNotification;
use Filament\Facades\Filament;
use Illuminate\Console\Command;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class InviteUserAction extends Action
{
    public string $commandSignature = 'users:invite {name?} {email?}';

    public function handle(string $name, string $email): User
    {
        $user = User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => Str::password(32),
        ]);

        $token = Password::broker('users')->createToken($user);
        $url = Filament::getPanel('platform')->getResetPasswordUrl($token, $user);
        $user->notify(new SetPasswordNotification($url));

        return $user;
    }

    public function asController(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
        ]);

        return response()->json($this->handle($data['name'], $data['email']), 201);
    }

    public function asCommand(Command $command): int
    {
        $name = $command->argument('name') ?: ($command->option('no-interaction') ? null : \Laravel\Prompts\text('Name'));
        $email = $command->argument('email') ?: ($command->option('no-interaction') ? null : \Laravel\Prompts\text('Email'));

        $user = $this->handle((string) $name, (string) $email);
        $command->info("Invited user {$user->id}");

        return self::SUCCESS;
    }
}
