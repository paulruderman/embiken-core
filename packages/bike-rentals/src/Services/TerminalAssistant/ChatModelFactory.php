<?php

declare(strict_types=1);

namespace Embiken\BikeRentals\Services\TerminalAssistant;

class ChatModelFactory
{
    public function make(): ChatModel
    {
        return match (config('services.terminal_assistant.driver')) {
            'ollama' => app(OllamaChatModel::class),
            default => throw new AssistantUnavailable('The Terminal assistant model is not enabled.'),
        };
    }
}
