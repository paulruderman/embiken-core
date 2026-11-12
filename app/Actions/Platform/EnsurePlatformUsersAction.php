<?php

namespace App\Actions\Platform;

use App\Actions\Action;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class EnsurePlatformUsersAction extends Action
{
    public string $commandSignature = 'platform:ensure-users';

    /**
     * @return array<int, User>
     */
    public function handle(): array
    {
        return DB::transaction(function (): array {
            return collect([
                ['name' => 'Paul', 'email' => 'paul@embiken.com'],
                ['name' => 'Platform', 'email' => 'platform@embiken.com'],
            ])->map(fn (array $attributes): User => User::query()->updateOrCreate(
                ['email' => $attributes['email']],
                [...$attributes, 'password' => '11111111', 'disabled_at' => null],
            ))->all();
        });
    }

    public function asController(): JsonResponse
    {
        return response()->json($this->handle());
    }

    public function asCommand(Command $command): int
    {
        $users = $this->handle();
        $command->info('Ensured platform users: '.collect($users)->pluck('email')->implode(', '));

        return self::SUCCESS;
    }
}
