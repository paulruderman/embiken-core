<?php

declare(strict_types=1);

namespace Embiken\BikeRentals\Services\TerminalAssistant;

interface ChatModel
{
    /**
     * @param  list<array<string, mixed>>  $messages
     * @param  list<array<string, mixed>>  $tools
     * @return array<string, mixed>
     */
    public function complete(array $messages, array $tools): array;
}
