<?php

namespace App\Actions\Platform;

use App\Actions\Action;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DisableUserAction extends Action
{
    public string $commandSignature = 'users:disable {user?}';

    public function handle(User $user): User
    {
        $user->disabled_at ??= now();
        $user->save();

        return $user;
    }

    public function asController(Request $request, User $user): JsonResponse
    {
        return response()->json($this->handle($user));
    }

    public function asCommand(Command $command): int
    {
        $id = $command->argument('user') ?: \Laravel\Prompts\text('User id');
        $this->handle(User::query()->findOrFail($id));
        $command->info('Disabled.');

        return self::SUCCESS;
    }
}
