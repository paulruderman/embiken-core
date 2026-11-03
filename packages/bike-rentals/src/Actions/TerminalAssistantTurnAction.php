<?php

declare(strict_types=1);

namespace Embiken\BikeRentals\Actions;

use App\Actions\Action;
use App\Actions\Concerns\AuthorizesStaff;
use Embiken\BikeRentals\Services\TerminalAssistant\TerminalAssistant;
use Illuminate\Console\Command;
use Illuminate\Routing\Router;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\ActionRequest;

class TerminalAssistantTurnAction extends Action
{
    use AuthorizesStaff;

    public string $commandSignature = 'terminal:assistant {--tenant=} {--message=}';

    /**
     * @param  list<array{role: 'user'|'assistant', content: string}>  $messages
     * @return array{message: string}
     */
    public function handle(array $messages, TerminalAssistant $assistant): array
    {
        if ($messages === [] || end($messages)['role'] !== 'user') {
            throw ValidationException::withMessages(['messages' => 'The latest chat turn must be a user message.']);
        }

        return $assistant->respond($messages);
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'messages' => ['required', 'array', 'min:1', 'max:12'],
            'messages.*.role' => ['required', 'in:user,assistant'],
            'messages.*.content' => ['required', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array{message: string}
     */
    public function asController(ActionRequest $request, TerminalAssistant $assistant): array
    {
        /** @var array{messages: list<array{role: 'user'|'assistant', content: string}>} $data */
        $data = $request->validated();

        return $this->handle($data['messages'], $assistant);
    }

    public static function routes(Router $router): void
    {
        $router->post('/terminal/assistant', static::class)
            ->middleware('throttle:20,1')
            ->name('terminal.assistant');
    }

    public function asCommand(Command $command, TerminalAssistant $assistant): int
    {
        $this->initializeTenancyFromCommand($command);
        $message = $this->requiredStringInput($command, $command->option('message'), 'Message');
        $result = $this->handle([['role' => 'user', 'content' => $message]], $assistant);
        $command->line($result['message']);

        return self::SUCCESS;
    }
}
