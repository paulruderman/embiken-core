<?php

namespace App\Filament\Shop\Concerns;

use App\Filament\Shop\Support\ShopSetupReadiness;
use Filament\Notifications\Notification;

trait RequiresShopSetup
{
    abstract protected static function shopSetupRequirement(): string;

    public function mount(): void
    {
        if ($this->redirectToMissingSetup()) {
            return;
        }

        parent::mount();
    }

    public function hydrate(): void
    {
        if ($this->redirectToMissingSetup()) {
            return;
        }

        parent::hydrate();
    }

    public function create(bool $another = false): void
    {
        if ($this->redirectToMissingSetup()) {
            return;
        }

        parent::create($another);
    }

    protected function getRedirectUrl(): string
    {
        $intent = $this->setupContinuationIntent();

        if ($intent === null) {
            return parent::getRedirectUrl();
        }

        $readiness = app(ShopSetupReadiness::class);
        $prerequisite = $readiness->firstMissingPrerequisite($intent);

        if ($prerequisite !== null && $prerequisite['action_url'] !== null) {
            session()->put('shop_setup_intent', [
                'key' => $intent,
                'expected_step' => $prerequisite['key'],
                'expires_at' => now()->addMinutes(30)->getTimestamp(),
            ]);

            return $readiness->setupTargetUrl($prerequisite['action_url'], $intent);
        }

        if ($intent === static::shopSetupRequirement()) {
            session()->forget('shop_setup_intent');

            return parent::getRedirectUrl();
        }

        session()->put('shop_setup_intent', [
            'key' => $intent,
            'expected_step' => $intent,
            'expires_at' => now()->addMinutes(30)->getTimestamp(),
        ]);

        return $readiness->continuationUrl($intent);
    }

    protected function getCreatedNotification(): ?Notification
    {
        $notification = parent::getCreatedNotification();
        $intent = $this->setupContinuationIntent();

        if ($notification === null || $intent === null) {
            return $notification;
        }

        $readiness = app(ShopSetupReadiness::class);

        if ($intent === static::shopSetupRequirement()) {
            if (! $readiness->step(static::shopSetupRequirement())['complete']) {
                return $notification;
            }

            return $readiness->addContinueSetupAction($notification, $readiness->step($intent)['module_id']);
        }

        if ($readiness->firstMissingPrerequisite($intent) !== null) {
            return $notification;
        }

        return $readiness->addContinueSetupAction($notification, setupTarget: $intent);
    }

    private function redirectToMissingSetup(): bool
    {
        $readiness = app(ShopSetupReadiness::class);
        $requestedTarget = request()->query('setup_target');

        if (is_string($requestedTarget) && $readiness->hasStep($requestedTarget)) {
            session()->put('shop_setup_intent', [
                'key' => $requestedTarget,
                'expected_step' => static::shopSetupRequirement(),
                'expires_at' => now()->addMinutes(30)->getTimestamp(),
            ]);
        }

        $target = $readiness->step(static::shopSetupRequirement());
        $prerequisite = $readiness->firstMissingPrerequisite(static::shopSetupRequirement());

        if ($prerequisite === null) {
            return false;
        }

        $setupUrl = $prerequisite['action_url'];

        if ($setupUrl === null) {
            return false;
        }

        $intent = $this->setupContinuationIntent() ?? static::shopSetupRequirement();
        session()->put('shop_setup_intent', [
            'key' => $intent,
            'expected_step' => $prerequisite['key'],
            'expires_at' => now()->addMinutes(30)->getTimestamp(),
        ]);

        Notification::make()
            ->title($prerequisite['action_label'])
            ->body('Complete this step before setting up '.$target['label'].'.')
            ->warning()
            ->send();

        $this->redirect($readiness->setupTargetUrl($setupUrl, $intent));

        return true;
    }

    private function setupContinuationIntent(): ?string
    {
        $intent = session('shop_setup_intent');

        if (! is_array($intent)
            || ! is_string($intent['key'] ?? null)
            || ($intent['expected_step'] ?? null) !== static::shopSetupRequirement()
            || ! is_int($intent['expires_at'] ?? null)
            || $intent['expires_at'] < now()->getTimestamp()) {
            session()->forget('shop_setup_intent');

            return null;
        }

        $readiness = app(ShopSetupReadiness::class);

        if (! $readiness->hasStep($intent['key'])) {
            session()->forget('shop_setup_intent');

            return null;
        }

        return $intent['key'];
    }
}
