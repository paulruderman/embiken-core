<?php

namespace App\Exceptions;

use RuntimeException;

class OccupancyUnavailable extends RuntimeException
{
    public function __construct(
        public string $reason,
        public ?int $lineId = null,
    ) {
        parent::__construct($reason);
    }
}
