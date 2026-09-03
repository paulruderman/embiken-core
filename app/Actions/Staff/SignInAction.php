<?php

namespace App\Actions\Staff;

use App\Actions\Action;
use App\Models\Staff;
use Illuminate\Console\Command;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Lorisleiva\Actions\ActionRequest;

class SignInAction extends Action
{
    public string $commandSignature = 'staff:sign-in {--tenant=} {--email=} {--password=}';

    public function handle(string $email, string $password): Staff
    {
        if (tenant()?->isSuspended()) {
            throw ValidationException::withMessages([
                'email' => 'This shop is unavailable.',
            ]);
        }

        $staff = Staff::query()->where('email', $email)->first();

        if ($staff === null || $staff->is_platform_manager || $staff->password === null) {
            throw ValidationException::withMessages([
                'email' => 'These credentials do not match our records.',
            ]);
        }

        if (! Auth::guard('staff')->attempt(['email' => $email, 'password' => $password])) {
            throw ValidationException::withMessages([
                'email' => 'These credentials do not match our records.',
            ]);
        }

        return $staff;
    }

    public function asController(Request $request): Staff|Response
    {
        if ($request->isMethod('get')) {
            return Inertia::render('Terminal/Login');
        }

        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        return $this->handle($data['email'], $data['password']);
    }

    public function htmlResponse(Staff|Response $result, ActionRequest $request): Response|RedirectResponse
    {
        if ($result instanceof Response) {
            return $result;
        }

        $request->session()->regenerate();

        return redirect()->intended('/prototype/terminal');
    }

    public function asCommand(Command $command): int
    {
        $this->initializeTenancyFromCommand($command);
        $this->handle(
            (string) ($command->option('email') ?: \Laravel\Prompts\text('Email')),
            (string) ($command->option('password') ?: \Laravel\Prompts\password('Password')),
        );
        $command->info('Signed in.');

        return self::SUCCESS;
    }
}
