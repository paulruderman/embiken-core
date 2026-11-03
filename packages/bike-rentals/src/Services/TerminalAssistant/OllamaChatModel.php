<?php

declare(strict_types=1);

namespace Embiken\BikeRentals\Services\TerminalAssistant;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class OllamaChatModel implements ChatModel
{
    /**
     * @param  list<array<string, mixed>>  $messages
     * @param  list<array<string, mixed>>  $tools
     * @return array<string, mixed>
     */
    public function complete(array $messages, array $tools): array
    {
        $baseUrl = rtrim((string) config('services.terminal_assistant.ollama_url'), '/');
        $model = (string) config('services.terminal_assistant.ollama_model');
        $timeout = (int) config('services.terminal_assistant.timeout', 90);

        if ($baseUrl === '' || $model === '') {
            throw new RuntimeException('The Terminal assistant model is not configured.');
        }

        $response = Http::baseUrl($baseUrl)
            ->acceptJson()
            ->asJson()
            ->timeout($timeout)
            ->post('/api/chat', [
                'model' => $model,
                'messages' => array_map($this->ollamaMessage(...), $messages),
                'tools' => $tools,
                'stream' => false,
                'think' => false,
            ])
            ->throw();

        $payload = $response->json();

        if (! is_array($payload) || ! is_array($payload['message'] ?? null)) {
            throw new RuntimeException('The Terminal assistant model returned an invalid response.');
        }

        $message = $payload['message'];
        $toolCalls = [];

        foreach (($message['tool_calls'] ?? []) as $toolCall) {
            $function = is_array($toolCall) ? ($toolCall['function'] ?? null) : null;

            if (! is_array($function) || ! is_string($function['name'] ?? null)) {
                continue;
            }

            $arguments = $function['arguments'] ?? [];

            if (is_string($arguments)) {
                $arguments = json_decode($arguments, true);
            }

            if (is_array($arguments)) {
                $toolCalls[] = [
                    'name' => $function['name'],
                    'arguments' => $arguments,
                ];
            }
        }

        return [
            'content' => is_string($message['content'] ?? null) ? $message['content'] : '',
            'tool_calls' => $toolCalls,
        ];
    }

    /**
     * @param  array<string, mixed>  $message
     * @return array<string, mixed>
     */
    private function ollamaMessage(array $message): array
    {
        if (($message['role'] ?? null) === 'assistant' && is_array($message['tool_calls'] ?? null)) {
            $message['tool_calls'] = array_map(static fn (array $toolCall): array => [
                'type' => 'function',
                'function' => [
                    'name' => $toolCall['name'],
                    'arguments' => $toolCall['arguments'],
                ],
            ], $message['tool_calls']);
        }

        return $message;
    }
}
