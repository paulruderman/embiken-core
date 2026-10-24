<?php

namespace App\Filament\Shop\Concerns;

use App\Filament\Shop\Support\ShopSetupReadiness;
use Filament\Actions\Action;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

trait HasBookSetupCallout
{
    public function getSubheading(): string|Htmlable|null
    {
        $step = $this->bookSetupCalloutStep();

        if ($step === null) {
            return null;
        }

        $callout = (string) __('app.setup_checklist.incomplete_book_callout');

        if ($step['action_url'] === null) {
            return str_replace(':step', $step['label'], $callout);
        }

        return new HtmlString(
            str_replace(
                ':step',
                '<a href="'.e($step['action_url']).'" wire:navigate class="font-medium text-primary-600 underline decoration-primary-300 underline-offset-2 hover:text-primary-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600 dark:text-primary-400 dark:hover:text-primary-300">'
                .e($step['action_label'])
                .'</a>',
                e($callout),
            ),
        );
    }

    protected function continueBookSetupAction(): ?Action
    {
        $step = $this->bookSetupCalloutStep();

        if ($step === null || $step['action_url'] === null) {
            return null;
        }

        return Action::make('continueSetup')
            ->label((string) __('app.setup_checklist.continue'))
            ->color('warning')
            ->icon('heroicon-m-arrow-right')
            ->url($step['action_url']);
    }

    /**
     * @return array{key: string, module_id: string, label: string, description: string, complete: bool, blocked: bool, status: string, action_label: string, action_url: string|null, next_step: string|null, tooltip: string, section: string}|null
     */
    protected function bookSetupCalloutStep(): ?array
    {
        return app(ShopSetupReadiness::class)->nextIncompleteBookSetupStep();
    }
}
