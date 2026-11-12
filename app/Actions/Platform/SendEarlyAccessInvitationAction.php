<?php

namespace App\Actions\Platform;

use App\Actions\Action;
use App\Http\Resources\EarlyAccessInvitationJsonResource;
use App\Models\EarlyAccessInvitation;
use App\Models\EarlyAccessSignup;
use App\Models\User;
use App\Notifications\EarlyAccessInvitationNotification;
use Illuminate\Console\Command;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class SendEarlyAccessInvitationAction extends Action
{
    public string $commandSignature = 'early-access:invite {signup?}';

    public string $commandDescription = 'Send an early access signup invitation.';

    public function handle(EarlyAccessSignup $signup, ?User $sentBy = null): EarlyAccessInvitation
    {
        abort_if($signup->status === 'closed', 422, 'Closed early access signups cannot be invited.');

        $token = Str::random(64);
        $previousStatus = $signup->status;
        $invitation = $signup->invitations()->create([
            'sent_by' => $sentBy?->getKey(),
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addDays(14),
        ]);
        $signup->forceFill(['status' => 'invited'])->save();

        $centralDomain = config('tenancy.central_domains.0', 'localhost');
        $routeName = Route::has('marketing.invite')
            ? 'marketing.invite'
            : 'marketing.invite.'.str_replace('.', '_', $centralDomain);
        $url = route($routeName, ['token' => $token]);
        try {
            Notification::route('mail', $signup->email)->notify(
                new EarlyAccessInvitationNotification($url, $signup->locale),
            );
        } catch (Throwable $exception) {
            $invitation->delete();
            $signup->forceFill(['status' => $previousStatus])->save();

            throw $exception;
        }

        return $invitation;
    }

    public function asController(Request $request, EarlyAccessSignup $signup): EarlyAccessInvitation
    {
        abort_unless($request->user() instanceof User, 403);

        return $this->handle($signup, $request->user());
    }

    public function jsonResponse(EarlyAccessInvitation $invitation): JsonResponse
    {
        return EarlyAccessInvitationJsonResource::make($invitation)->response()->setStatusCode(201);
    }

    public function asCommand(Command $command): int
    {
        $id = $command->argument('signup');

        if (! is_numeric($id)) {
            if ($command->option('no-interaction')) {
                throw ValidationException::withMessages(['signup' => 'The signup ID is required.']);
            }

            $id = \Laravel\Prompts\text('Early access signup ID');
        }

        $signup = EarlyAccessSignup::query()->findOrFail((int) $id);
        $invitation = $this->handle($signup);
        $command->info("Invitation {$invitation->id} sent to {$signup->email}.");

        return self::SUCCESS;
    }
}
