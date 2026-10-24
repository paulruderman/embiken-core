<?php

namespace App\Filament\Shop\Concerns;

use App\Filament\Shop\Support\ShopSetupReadiness;
use Filament\Actions\Action;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

trait HasSetupPrerequisiteCallout
{
    abstract protected static function setupPrerequisiteStep(): string;

    public function getSubheading(): string|Htmlable|null
    {
        $readiness = app(ShopSetupReadiness::class);
        $step = $readiness->step(static::setupPrerequisiteStep());

        if (! $step['blocked'] || $step['action_url'] === null) {
            return null;
        }

        $callout = (string) __('app.setup_checklist.prerequisite_callout', [
            'item' => Str::lower($step['label']),
        ]);

        return new HtmlString(
            str_replace(
                ':action',
                '<a href="'.e($step['action_url']).'" wire:navigate class="font-medium text-primary-600 underline decoration-primary-300 underline-offset-2 hover:text-primary-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600 dark:text-primary-400 dark:hover:text-primary-300">'
                .e($step['action_label'])
                .'</a>',
                e($callout),
            ),
        );
    }

    protected function continueSetupPrerequisiteAction(): ?Action
    {
        $readiness = app(ShopSetupReadiness::class);
        $step = $readiness->step(static::setupPrerequisiteStep());

        if (! $step['blocked'] || $step['action_url'] === null) {
            return null;
        }

        return Action::make('continueSetup')
            ->label((string) __('app.setup_checklist.continue'))
            ->color('warning')
            ->icon('heroicon-m-arrow-right')
            ->url($step['action_url']);
    }
}
