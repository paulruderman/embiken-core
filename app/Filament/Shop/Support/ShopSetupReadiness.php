<?php

namespace App\Filament\Shop\Support;

use App\Filament\Shop\Resources\Locations\LocationResource;
use App\Models\Location;
use App\Support\ModuleRegistry;
use Closure;
use Embiken\BikeFleet\Filament\Resources\BikeCategories\BikeCategoryResource;
use Embiken\BikeFleet\Filament\Resources\BikeModels\BikeModelResource;
use Embiken\BikeFleet\Filament\Resources\BikeModelVariants\BikeModelVariantResource;
use Embiken\BikeFleet\Filament\Resources\Bikes\BikeResource;
use Embiken\BikeFleet\Filament\Resources\ServiceRequests\ServiceRequestResource;
use Embiken\BikeFleet\Models\Bike;
use Embiken\BikeFleet\Models\BikeCategory;
use Embiken\BikeFleet\Models\BikeModel;
use Embiken\BikeFleet\Models\BikeModelVariant;
use Embiken\BikeRentals\Filament\Resources\RentalPackages\RentalPackageResource;
use Embiken\BikeRentals\Models\RentalPackage;
use Filament\Actions\Action;
use Filament\Tables\Table;

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
     *     tooltip: string
     * }>
     */
    public function checklist(): array
    {
        $steps = [];

        foreach ($this->definitions() as $key => $definition) {
            if (! ($definition['show_in_checklist'] ?? true)) {
                continue;
            }

            $steps[] = $this->present($key, $definition);
        }

        return $steps;
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
     *     tooltip: string
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

    public function setupTargetUrl(string $url, string $key): string
    {
        return $this->withSetupTarget($url, $key);
    }

    /**
     * Return the earliest missing prerequisite for a step, or null if its prerequisites are met.
     *
     * @return array{key: string, label: string, description: string, complete: bool, blocked: bool, status: string, action_label: string, action_url: string|null, next_step: string|null, tooltip: string}|null
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

        return $table
            ->emptyStateHeading($heading)
            ->emptyStateDescription($description)
            ->emptyStateActions($step['action_url'] === null
                ? []
                : [
                    Action::make('continueSetup')
                        ->label($step['action_label'])
                        ->url($step['action_url']),
                ]);
    }

    /**
     * @return array<string, array{
     *     label: string,
     *     description: string,
     *     action_label: string|Closure,
     *     action_url: Closure,
     *     is_complete: Closure,
     *     prerequisites: list<string>,
     *     show_in_checklist?: bool
     * }>
     */
    private function definitions(): array
    {
        $steps = [];

        if (app(ModuleRegistry::class)->isEnabled('embiken/bike-fleet')) {
            $steps += [
                self::BikeCategories => [
                    'label' => 'Bike categories',
                    'description' => 'Categories organize the kinds of bikes in your catalog.',
                    'action_label' => 'Add a bike category',
                    'action_url' => fn (): string => BikeCategoryResource::getUrl('create'),
                    'is_complete' => fn (): bool => BikeCategory::query()->exists(),
                    'prerequisites' => [],
                ],
                self::BikeModels => [
                    'label' => 'Bike models',
                    'description' => 'Models group the variants that share a bike design.',
                    'action_label' => 'Add a bike model',
                    'action_url' => fn (): string => BikeModelResource::getUrl('create'),
                    'is_complete' => fn (): bool => BikeModel::query()->exists(),
                    'prerequisites' => [self::BikeCategories],
                ],
                self::BikeModelVariants => [
                    'label' => 'Bike model variants',
                    'description' => 'Variants define the sizes or classes you can offer.',
                    'action_label' => 'Add a bike model variant',
                    'action_url' => fn (): string => BikeModelVariantResource::getUrl('create'),
                    'is_complete' => fn (): bool => BikeModelVariant::query()->exists(),
                    'prerequisites' => [self::BikeModels],
                ],
                self::Bikes => [
                    'label' => 'Bikes',
                    'description' => 'A bike needs a model variant before it can be added.',
                    'action_label' => 'Add a bike',
                    'action_url' => fn (): string => BikeResource::getUrl('create'),
                    'is_complete' => fn (): bool => Bike::query()->exists(),
                    'prerequisites' => [self::BikeModelVariants],
                ],
                self::ServiceRequests => [
                    'label' => 'Service requests',
                    'description' => 'Service requests are attached to a bike in your fleet.',
                    'action_label' => 'Add a bike',
                    'action_url' => fn (): string => BikeResource::getUrl('create'),
                    'is_complete' => fn (): bool => Bike::query()->exists(),
                    'prerequisites' => [self::Bikes],
                    'show_in_checklist' => false,
                ],
            ];
        }

        $location = Location::query()->first();

        if ($location !== null) {
            $steps[self::LocationHours] = [
                'label' => 'Opening hours',
                'description' => 'Book needs opening hours for customers to choose a rental interval.',
                'action_label' => 'Set opening hours',
                'action_url' => fn (): string => LocationResource::getUrl('edit', [
                    'record' => $location->getKey(),
                ]),
                'is_complete' => fn (): bool => $location->hours()->exists(),
                'prerequisites' => [],
            ];
        }

        if (app(ModuleRegistry::class)->isEnabled('embiken/bike-rentals')) {
            $steps[self::RentalPackages] = [
                'label' => 'Rental packages',
                'description' => 'Packages define rental pricing and Book payment requirements.',
                'action_label' => 'Add a rental package',
                'action_url' => fn (): string => RentalPackageResource::getUrl('create'),
                'is_complete' => fn (): bool => RentalPackage::query()->exists(),
                'prerequisites' => [],
            ];
            $steps[self::BookOffer] = [
                'label' => 'Book offer',
                'description' => 'A Book-visible package needs at least one variant and rate to offer bikes.',
                'action_label' => fn (): string => $this->bookOfferActionLabel(),
                'action_url' => fn (): string => $this->bookOfferActionUrl(),
                'is_complete' => fn (): bool => RentalPackage::query()
                    ->where('book_visible', true)
                    ->whereHas('variants')
                    ->exists(),
                'prerequisites' => array_key_exists(self::BikeModelVariants, $steps)
                    ? [self::BikeModelVariants]
                    : [],
            ];

            if (array_key_exists(self::Bikes, $steps)) {
                $steps[self::BookableFleet] = [
                    'label' => 'Bookable bike',
                    'description' => 'Book can offer bikes that are in service and self-bookable.',
                    'action_label' => 'Review bikes',
                    'action_url' => fn (): string => BikeResource::getUrl('index'),
                    'is_complete' => fn (): bool => Bike::query()
                        ->where('in_service', true)
                        ->where('self_bookable', true)
                        ->exists(),
                    'prerequisites' => [self::Bikes],
                ];
            }
        }

        return $steps;
    }

    /**
     * @param  array{label: string, description: string, action_label: string|Closure, action_url: Closure, is_complete: Closure, prerequisites: list<string>, show_in_checklist?: bool}  $definition
     * @return array{key: string, label: string, description: string, complete: bool, blocked: bool, status: string, action_label: string, action_url: string|null, next_step: string|null, tooltip: string}
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
            $status = 'Waiting on '.$blocker['label'];
            $tooltip = 'Set up '.$blocker['label'].' first.';
        } else {
            $actionUrl = $definition['action_url']();
            $actionLabel = is_string($definition['action_label'])
                ? $definition['action_label']
                : $definition['action_label']();
            $status = 'Ready to set up';
            $tooltip = $definition['description'];
        }

        if (! $complete && $actionUrl !== null) {
            $actionUrl = $this->withSetupTarget($actionUrl, $key);
        }

        return [
            'key' => $key,
            'label' => $definition['label'],
            'description' => $definition['description'],
            'complete' => $complete,
            'blocked' => ! $complete && $blocker !== null,
            'status' => $status,
            'action_label' => $actionLabel,
            'action_url' => $actionUrl,
            'next_step' => $blocker['key'] ?? null,
            'tooltip' => $tooltip,
        ];
    }

    private function bookOfferActionLabel(): string
    {
        $visiblePackage = RentalPackage::query()
            ->where('book_visible', true)
            ->first();

        if ($visiblePackage !== null) {
            return 'Add package variants';
        }

        return RentalPackage::query()->exists()
            ? 'Choose a Book-visible package'
            : 'Create a rental package';
    }

    private function bookOfferActionUrl(): string
    {
        $visiblePackage = RentalPackage::query()
            ->where('book_visible', true)
            ->first();

        if ($visiblePackage !== null) {
            return RentalPackageResource::getUrl('edit', ['record' => $visiblePackage]);
        }

        if (RentalPackage::query()->exists()) {
            return RentalPackageResource::getUrl('index');
        }

        return RentalPackageResource::getUrl('create');
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
