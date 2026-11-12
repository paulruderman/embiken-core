<?php

declare(strict_types=1);

namespace Embiken\BikeRentals\Services\TerminalAssistant;

use RuntimeException;

class AssistantUnavailable extends RuntimeException
{
    public function __construct(string $message, public readonly ?string $userMessage = null)
    {
        parent::__construct($message);
    }
}
