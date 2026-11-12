<?php

namespace App\Actions\Platform;

use App\Actions\Action;
use App\Models\BillingPlan;
use App\Models\Location;
use App\Models\Tenant;
use App\Support\Locales;
use App\Support\ModuleRegistry;
use Illuminate\Console\Command;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CreateTenantAction extends Action
{
    public string $commandSignature = 'tenants:create {name?} {--domain=} {--timezone=America/New_York} {--currency=usd} {--manager-name=} {--manager-email=} {--plan=starter} {--address-line-1=} {--address-line-2=} {--city=} {--administrative-area=} {--postal-code=} {--country-code=}';

    public string $commandDescription = 'Provision a shop Tenant, Location, Manager invite, and Platform Manager.';

    public function handle(
        string $name,
        string $domain,
        string $timezone,
        string $currency,
        string $managerName,
        string $managerEmail,
        ?BillingPlan $billingPlan = null,
        string $siteLocale = Locales::Default,
        ?string $addressLine1 = null,
        ?string $addressLine2 = null,
        ?string $city = null,
        ?string $administrativeArea = null,
        ?string $postalCode = null,
        ?string $countryCode = null,
    ): Tenant {
        $tenant = Tenant::create([
            'name' => $name,
            'billing_plan_id' => $billingPlan->slug ?? BillingPlan::query()->where('slug', 'starter')->value('slug'),
            'manager_name' => $managerName,
            'manager_email' => $managerEmail,
            'charges_enabled' => false,
            'transfers_active' => false,
            'modules' => ModuleRegistry::defaultTenantModuleIds(),
        ]);

        $tenant->domains()->create(['domain' => $domain]);

        $contentLocale = Locales::translationLocale($siteLocale);
        $tenant->run(function () use ($name, $timezone, $currency, $managerName, $managerEmail, $contentLocale, $addressLine1, $addressLine2, $city, $administrativeArea, $postalCode, $countryCode): void {
            app(ProvisionShopAction::class)(
                shopName: $name,
                timezone: $timezone,
                currency: $currency,
                managerName: $managerName,
                managerEmail: $managerEmail,
                addressLine1: $addressLine1,
                addressLine2: $addressLine2,
                city: $city,
                administrativeArea: $administrativeArea,
                postalCode: $postalCode,
                countryCode: $countryCode,
            );
            Location::query()->firstOrFail()->forceFill([
                'enabled_content_locales' => [$contentLocale],
                'default_content_locale' => $contentLocale,
            ])->save();
        });

        app(StartExpressAccountLinkAction::class)($tenant);

        return $tenant->refresh();
    }

    public function asController(Request $request): JsonResponse
    {
        $data = $request->validate($this->rules());

        return $this->jsonResponse($this->handle(
            $data['name'],
            $data['domain'],
            $data['timezone'],
            $data['currency'],
            $data['manager_name'],
            $data['manager_email'],
            BillingPlan::query()->whereKey($data['plan'] ?? 'starter')->firstOrFail(),
            addressLine1: $data['address_line_1'] ?? null,
            addressLine2: $data['address_line_2'] ?? null,
            city: $data['city'] ?? null,
            administrativeArea: $data['administrative_area'] ?? null,
            postalCode: $data['postal_code'] ?? null,
            countryCode: isset($data['country_code']) ? strtoupper($data['country_code']) : null,
        ));
    }

    public function jsonResponse(Tenant $tenant): JsonResponse
    {
        return response()->json($tenant->load('domains'));
    }

    public function asCommand(Command $command): int
    {
        $name = $this->stringOption($command, 'name', $command->argument('name'));
        $domain = $this->stringOption($command, 'domain');
        $timezone = $this->stringOption($command, 'timezone', default: 'America/New_York');
        $currency = $this->stringOption($command, 'currency', default: 'usd');
        $managerName = $this->stringOption($command, 'manager-name');
        $managerEmail = $this->stringOption($command, 'manager-email');
        $plan = BillingPlan::query()->whereKey($this->stringOption($command, 'plan', default: 'starter'))->firstOrFail();

        $tenant = $this->handle(
            $name,
            $domain,
            $timezone,
            $currency,
            $managerName,
            $managerEmail,
            $plan,
            addressLine1: $this->optionalStringValue($command->option('address-line-1'), 'Address line 1'),
            addressLine2: $this->optionalStringValue($command->option('address-line-2'), 'Address line 2'),
            city: $this->optionalStringValue($command->option('city'), 'City'),
            administrativeArea: $this->optionalStringValue($command->option('administrative-area'), 'State or region'),
            postalCode: $this->optionalStringValue($command->option('postal-code'), 'Postal code'),
            countryCode: $this->optionalStringValue($command->option('country-code'), 'Country code'),
        );
        $command->info("Created tenant {$tenant->id}");

        return self::SUCCESS;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'domain' => ['required', 'string', 'max:255', 'unique:domains,domain'],
            'timezone' => ['required', 'timezone'],
            'currency' => ['required', 'string', 'regex:/^[A-Za-z]{3}$/'],
            'manager_name' => ['required', 'string', 'max:255'],
            'manager_email' => ['required', 'email'],
            'address_line_1' => ['nullable', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'administrative_area' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:32'],
            'country_code' => ['nullable', 'string', 'size:2', 'alpha'],
            'plan' => ['sometimes', 'required', 'string', 'exists:billing_plans,slug'],
        ];
    }

    private function stringOption(Command $command, string $name, mixed $fallback = null, ?string $default = null): string
    {
        $value = $fallback ?? $command->option($name) ?? $default;

        if (is_string($value) && $value !== '') {
            return $value;
        }

        if ($command->option('no-interaction')) {
            throw ValidationException::withMessages([$name => "The {$name} value is required."]);
        }

        return (string) \Laravel\Prompts\text(str_replace('-', ' ', $name));
    }
}
