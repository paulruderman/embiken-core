<?php

namespace App\Http\Middleware;

use App\Enums\BikeAssignmentPolicy;
use App\Enums\ReturnSituation;
use App\Enums\StaffRole;
use App\Enums\Weekday;
use App\Support\Locales;
use App\Support\ModuleRegistry;
use Illuminate\Http\Request;
use Inertia\Middleware;
use LogicException;

class HandleInertiaRequests extends Middleware
{
    /**
     * @var string
     */
    protected $rootView = 'app';

    /**
     * @var array<int, string>
     */
    protected $withoutSsr = [
        'prototype/terminal',
        'Terminal/prototype/Index',
        'prototype/weather',
        'Weather/prototype/Index',
    ];

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'locale' => Locales::intlLocale($request->attributes->get('embiken.locale')),
            'supportedLocales' => array_map(
                static fn (string $locale): array => [
                    'value' => $locale,
                    'tag' => Locales::intlLocale($locale),
                    'label' => Locales::labels()[$locale],
                ],
                Locales::all(),
            ),
            'auth' => [
                'user' => $request->user(),
            ],
            'enabledModules' => array_map(
                fn ($module): string => $module->id(),
                app(ModuleRegistry::class)->resolvedEnabledModules(),
            ),
            'enums' => $this->enumMeta(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function enumMeta(): array
    {
        $enums = [
            'bikeAssignmentPolicy' => BikeAssignmentPolicy::getFrontendLookupTable(),
            'returnSituation' => ReturnSituation::getFrontendLookupTable(),
            'staffRole' => StaffRole::getFrontendLookupTable(),
            'weekday' => Weekday::getFrontendLookupTable(),
        ];

        foreach (app(ModuleRegistry::class)->resolvedEnabledModules() as $module) {
            foreach ($module->frontendEnums() as $enumClass) {
                if (! method_exists($enumClass, 'getFrontendLookupTable')) {
                    throw new LogicException("Frontend enum [{$enumClass}] must define getFrontendLookupTable().");
                }

                $key = lcfirst(class_basename($enumClass));
                $enums[$key] = $enumClass::getFrontendLookupTable();
            }
        }

        return $enums;
    }
}
