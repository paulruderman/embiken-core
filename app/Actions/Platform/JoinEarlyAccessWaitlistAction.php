<?php

namespace App\Actions\Platform;

use App\Actions\Action;
use App\Http\Resources\EarlyAccessSignupJsonResource;
use App\Models\EarlyAccessSignup;
use App\Notifications\EarlyAccessReceiptNotification;
use App\Support\Locales;
use Illuminate\Console\Command;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class JoinEarlyAccessWaitlistAction extends Action
{
    public string $commandSignature = 'early-access:join {email?} {--name=} {--shop-name=} {--message=}';

    public string $commandDescription = 'Add or update an early access interest signup.';

    public function handle(
        string $email,
        ?string $name = null,
        ?string $shopName = null,
        ?string $message = null,
        string $locale = 'en',
    ): EarlyAccessSignup {
        Validator::make([
            'email' => $email,
            'name' => $name,
            'shop_name' => $shopName,
            'message' => $message,
        ], $this->rules())->validate();

        $email = Str::lower(trim($email));
        $signup = EarlyAccessSignup::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'shop_name' => $shopName,
                'message' => $message,
                'locale' => in_array($locale, ['en', 'fr', 'es'], true) ? $locale : 'en',
            ],
        );

        Notification::route('mail', $email)->notify(new EarlyAccessReceiptNotification($signup->locale));

        return $signup;
    }

    public function asController(Request $request): EarlyAccessSignup
    {
        $data = $request->validate($this->rules());
        $locale = Locales::translationLocale($request->attributes->get('embiken.locale'));

        $signup = $this->handle(
            $data['email'],
            $data['name'] ?? null,
            $data['shop_name'] ?? null,
            $data['message'] ?? null,
            $locale,
        );

        return $signup;
    }

    public function jsonResponse(EarlyAccessSignup $signup): JsonResponse
    {
        return EarlyAccessSignupJsonResource::make($signup)->response()->setStatusCode(201);
    }

    public function asCommand(Command $command): int
    {
        $email = $command->argument('email');

        if (! is_string($email) || $email === '') {
            if ($command->option('no-interaction')) {
                throw ValidationException::withMessages(['email' => 'The email value is required.']);
            }

            $email = (string) \Laravel\Prompts\text('Email address');
        }

        $signup = $this->handle(
            $email,
            $this->optionalStringValue($command->option('name'), 'Name'),
            $this->optionalStringValue($command->option('shop-name'), 'Shop name'),
            $this->optionalStringValue($command->option('message'), 'Message'),
        );
        $command->info("Saved early access signup {$signup->id}.");

        return self::SUCCESS;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'shop_name' => ['nullable', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
