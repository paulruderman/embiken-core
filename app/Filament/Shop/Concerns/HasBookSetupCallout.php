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
        $step = app(ShopSetupReadiness::class)->nextIncompleteSetupStep();

        if ($step === null) {
            return null;
        }

        if ($step['action_url'] === null) {
            return 'Online Book setup is incomplete. Next: '.$step['label'].'.';
        }

        return new HtmlString(
            'Online Book setup is incomplete. Next: '
                .'<a href="'.e($step['action_url']).'" wire:navigate class="font-medium text-primary-600 underline decoration-primary-300 underline-offset-2 hover:text-primary-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600 dark:text-primary-400 dark:hover:text-primary-300">'
                .e($step['action_label'])
                .'</a>.',
        );
    }

    protected function continueBookSetupAction(): ?Action
    {
        $step = app(ShopSetupReadiness::class)->nextIncompleteSetupStep();

        if ($step === null || $step['action_url'] === null) {
            return null;
        }

        return Action::make('continueSetup')
            ->label('Continue setup')
            ->color('warning')
            ->icon('heroicon-m-arrow-right')
            ->url($step['action_url']);
    }
}
