<?php

namespace App\Filament\Shop\Support;

use App\Filament\Shop\Pages\ShopLandingPageEditor;
use App\Support\ModuleRegistry;
use App\Support\TranslationCompleteness;
use Closure;
use Embiken\BikeFleet\BikeFleetModule;
use Embiken\BikeFleet\Filament\Resources\BikeModels\BikeModelResource;
use Embiken\BikeFleet\Filament\Resources\BikeModelVariants\BikeModelVariantResource;
use Embiken\BikeFleet\Filament\Resources\Bikes\BikeResource;
use Embiken\BikeFleet\Filament\Resources\ServiceRequests\ServiceRequestResource;
use Embiken\BikeRentalSelfBooking\BikeRentalSelfBookingModule;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class ShopSetupReadiness
{
    public const string BikeCategories = 'bike_categories';

    public const string BikeModels = 'bike_models';

    public const string BikeModelVariants = 'bike_model_variants';

    public const string Bikes = 'bikes';

    public const string ServiceRequests = 'service_requests';

    public const string LocationHours = 'location_hours';

    public const string RentalPackages = 'rental_packages';

    public const string BookOffer = 'book_offer';

    public const string BookableFleet = 'bookable_fleet';

    public const string Translations = 'translations';

    /**
     * @return list<array{
     *     key: string,
     *     label: string,
     *     description: string,
     *     complete: bool,
     *     blocked: bool,
     *     status: string,
     *     action_label: string,
     *     action_url: string|null,
     *     next_step: string|null,
     *     tooltip: string,
     *     section: string,
     *     module_id: string
     * }>
     */
    public function checklist(?string $moduleId = null): array
    {
        $steps = [];

        foreach ($this->definitions() as $key => $definition) {
            if (! $definition['show_in_checklist'] || ($moduleId !== null && $definition['module_id'] !== $moduleId)) {
                continue;
            }

            $presented = $this->present($key, $definition);

            if ($key === self::Translations && $presented['complete']) {
                continue;
            }

            $steps[] = $presented;
        }

        return $steps;
    }

    /**
     * @return array{key: string, module_id: string, label: string, description: string, complete: bool, blocked: bool, status: string, action_label: string, action_url: string|null, next_step: string|null, tooltip: string, section: string}|null
     */
    public function nextIncompleteSetupStep(?string $moduleId = null): ?array
    {
        foreach ($this->checklist($moduleId) as $step) {
            if (! $step['complete']) {
                return $step;
            }
        }

        return null;
    }

    /**
     * Return the next missing step required to configure online booking.
     *
     * @return array{key: string, module_id: string, label: string, description: string, complete: bool, blocked: bool, status: string, action_label: string, action_url: string|null, next_step: string|null, tooltip: string, section: string}|null
     */
    public function nextIncompleteBookSetupStep(): ?array
    {
        return $this->nextIncompleteSetupStep(BikeRentalSelfBookingModule::ID);
    }

    /**
     * @return array{
     *     key: string,
     *     label: string,
     *     description: string,
     *     complete: bool,
     *     blocked: bool,
     *     status: string,
     *     action_label: string,
     *     action_url: string|null,
     *     next_step: string|null,
     *     tooltip: string,
     *     section: string,
     *     module_id: string
     * }
     */
    public function step(string $key): array
    {
        $definition = $this->definitions()[$key] ?? null;

        if ($definition === null) {
            throw new \InvalidArgumentException("Unknown shop setup step [{$key}].");
        }

        return $this->present($key, $definition);
    }

    public function hasStep(string $key): bool
    {
        return array_key_exists($key, $this->definitions());
    }

    public function destinationUrl(string $key): string
    {
        return match ($key) {
            self::ServiceRequests => ServiceRequestResource::getUrl('create'),
            default => ($this->definitions()[$key]['action_url'])(),
        };
    }

    public function continuationUrl(string $key): string
    {
        return $this->withSetupTarget($this->destinationUrl($key), $key);
    }

    public function addContinueSetupAction(
        Notification $notification,
        ?string $moduleId = null,
        ?string $setupTarget = null,
    ): Notification {
        if ($setupTarget !== null && $this->hasStep($setupTarget)) {
            $nextStep = $this->firstMissingPrerequisite($setupTarget);

            if ($nextStep === null && ! $this->step($setupTarget)['complete']) {
                $nextStep = $this->step($setupTarget);
            }

            if ($nextStep === null) {
                $moduleId = $this->step($setupTarget)['module_id'];
            }
        }

        $nextStep ??= $this->nextIncompleteSetupStep($moduleId);

        if ($nextStep === null || $nextStep['action_url'] === null) {
            return $notification;
        }

        $actionUrl = $setupTarget === null
            ? $nextStep['action_url']
            : $this->withSetupTarget($nextStep['action_url'], $setupTarget);

        return $notification
            ->body('Next: '.$nextStep['label'].'.')
            ->actions([
                Action::make('continueSetup')
                    ->label('Continue setup')
                    ->url($actionUrl),
            ]);
    }

    public function setupTargetUrl(string $url, string $key): string
    {
        return $this->withSetupTarget($url, $key);
    }

    /**
     * Return the earliest missing prerequisite for a step, or null if its prerequisites are met.
     *
     * @return array{key: string, module_id: string, label: string, description: string, complete: bool, blocked: bool, status: string, action_label: string, action_url: string|null, next_step: string|null, tooltip: string, section: string}|null
     */
    public function firstMissingPrerequisite(string $key): ?array
    {
        $definitions = $this->definitions();
        $definition = $definitions[$key] ?? null;

        if ($definition === null) {
            throw new \InvalidArgumentException("Unknown shop setup step [{$key}].");
        }

        foreach ($definition['prerequisites'] as $prerequisiteKey) {
            $prerequisite = $definitions[$prerequisiteKey];

            if ($prerequisite['is_complete']()) {
                continue;
            }

            return $this->firstMissingPrerequisite($prerequisiteKey)
                ?? $this->present($prerequisiteKey, $prerequisite);
        }

        return null;
    }

    public function configureEmptyState(Table $table, string $key, string $heading): Table
    {
        $step = $this->step($key);
        $description = $step['blocked']
            ? $step['tooltip'].' '.$step['description']
            : $step['description'];

        if ($step['blocked'] && $step['action_url'] !== null) {
            $description = new HtmlString(
                e($description)
                    .' Next: <a href="'.e($step['action_url']).'" wire:navigate class="font-medium text-primary-600 underline decoration-primary-300 underline-offset-2 hover:text-primary-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600 dark:text-primary-400 dark:hover:text-primary-300">'
                    .e($step['action_label'])
                    .'</a>.',
            );
        }

        return $table
            ->emptyStateHeading($heading)
            ->emptyStateDescription($description)
            ->emptyStateActions($step['action_url'] === null
                ? []
                : [
                    Action::make('continueSetup')
                        ->label($step['blocked'] ? 'Continue setup' : $step['action_label'])
                        ->url($step['action_url']),
                ]);
    }

    /**
     * @return array<string, array{
     *     module_id: string,
     *     label: string,
     *     description: string,
     *     action_label: string|Closure,
     *     action_url: Closure,
     *     is_complete: Closure,
     *     prerequisites: list<string>,
     *     show_in_checklist: bool
     * }>
     */
    private function definitions(): array
    {
        $steps = [];

        foreach (app(ModuleRegistry::class)->resolvedEnabledModules() as $module) {
            foreach ($module->setupSteps() as $step) {
                $steps[$step->key] = [
                    'module_id' => $module->id(),
                    'label' => $step->label,
                    'description' => $step->description,
                    'action_label' => $step->actionLabel,
                    'action_url' => $step->actionUrl,
                    'is_complete' => $step->isComplete,
                    'prerequisites' => $step->prerequisites,
                    'show_in_checklist' => $step->showInChecklist,
                ];
            }
        }

        if (app(ModuleRegistry::class)->isEnabled(BikeFleetModule::ID)) {
            $steps[self::Translations] = [
                'module_id' => BikeFleetModule::ID,
                'label' => (string) __('app.translations.setup_step'),
                'description' => (string) __('app.translations.setup_description'),
                'action_label' => (string) __('app.translations.review'),
                'action_url' => fn (): string => app(TranslationCompleteness::class)->firstMissingTranslationUrl()
                    ?? ShopLandingPageEditor::getUrl(),
                'is_complete' => fn (): bool => ! app(TranslationCompleteness::class)->hasMissingTranslations(),
                'prerequisites' => [],
                'show_in_checklist' => true,
            ];
        }

        return $steps;
    }

    /**
     * @return list<array{label: string, description: string, actions: list<array{label: string, url: string}>}>
     */
    public function recommendations(?string $moduleId = null): array
    {
        $steps = $this->checklist($moduleId);

        if ($steps === []
            || count(array_filter($steps, fn (array $step): bool => ! $step['complete'])) > 0
            || ! app(ModuleRegistry::class)->isEnabled('embiken/bike-fleet')
            || ($moduleId !== null && $moduleId !== 'embiken/bike-fleet')) {
            return [];
        }

        return [[
            'label' => 'Add catalog photos',
            'description' => 'Photos help customers recognize your bikes. Add them to bikes, variants, or models; bike photos take precedence when displaying an image.',
            'actions' => [
                ['label' => 'Bikes', 'url' => BikeResource::getUrl('index')],
                ['label' => 'Variants', 'url' => BikeModelVariantResource::getUrl('index')],
                ['label' => 'Models', 'url' => BikeModelResource::getUrl('index')],
            ],
        ]];
    }

    /**
     * @param  array{module_id: string, label: string, description: string, action_label: string|Closure, action_url: Closure, is_complete: Closure, prerequisites: list<string>, show_in_checklist: bool}  $definition
     * @return array{key: string, module_id: string, label: string, description: string, complete: bool, blocked: bool, status: string, action_label: string, action_url: string|null, next_step: string|null, tooltip: string, section: string}
     */
    private function present(string $key, array $definition): array
    {
        $complete = $definition['is_complete']();
        $blocker = $complete ? null : $this->firstMissingPrerequisite($key);

        if ($complete) {
            $actionUrl = null;
            $actionLabel = 'Complete';
            $status = 'Complete';
            $tooltip = $definition['label'].' is set up.';
        } elseif ($blocker !== null) {
            $actionUrl = $blocker['action_url'];
            $actionLabel = $blocker['action_label'];
            $blockerLabel = $blocker['label'] === 'Opening hours'
                ? 'opening hours'
                : Str::lower(Str::singular($blocker['label']));
            $status = 'Pending '.$blockerLabel.' setup';
            $tooltip = 'Set up '.$blocker['label'].' first.';
        } else {
            $actionUrl = $definition['action_url']();
            $actionLabel = is_string($definition['action_label'])
                ? $definition['action_label']
                : $definition['action_label']();
            $status = 'Ready to set up';
            $tooltip = 'Ready to set up. '.$definition['description'];
        }

        if (! $complete && $actionUrl !== null) {
            $actionUrl = $this->withSetupTarget($actionUrl, $key);
        }

        return [
            'key' => $key,
            'module_id' => $definition['module_id'],
            'label' => $definition['label'],
            'description' => $definition['description'],
            'complete' => $complete,
            'blocked' => ! $complete && $blocker !== null,
            'status' => $status,
            'action_label' => $actionLabel,
            'action_url' => $actionUrl,
            'next_step' => $blocker['key'] ?? null,
            'tooltip' => $tooltip,
            'section' => $definition['module_id'],
        ];
    }

    private function withSetupTarget(string $url, string $target): string
    {
        $url = preg_replace('/([?&])setup_target=[^&]*/', '$1setup_target='.rawurlencode($target), $url) ?? $url;

        if (str_contains($url, 'setup_target=')) {
            return $url;
        }

        return $url.(str_contains($url, '?') ? '&' : '?').'setup_target='.rawurlencode($target);
    }
}
