<?php

declare(strict_types=1);

namespace App\Support;

use Closure;

final readonly class SetupStepDefinition
{
    /**
     * @param  list<string>  $prerequisites
     */
    public function __construct(
        public string $key,
        public string $label,
        public string $description,
        public string|Closure $actionLabel,
        public Closure $actionUrl,
        public Closure $isComplete,
        public array $prerequisites = [],
        public bool $showInChecklist = true,
    ) {}
}
