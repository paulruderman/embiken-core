<?php

namespace App\Actions\Platform;

use App\Actions\Action;
use App\Models\BillingPlan;
use App\Models\Domain;
use App\Models\Tenant;
use App\Support\Locales;
use Illuminate\Console\Command;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PublicSignupAction extends Action
{
    public string $commandSignature = 'tenants:signup {name?} {domain?} {manager-name?} {manager-email?} {--plan=starter} {--timezone=America/New_York} {--currency=usd} {--address-line-1=} {--address-line-2=} {--city=} {--administrative-area=} {--postal-code=} {--country-code=}';

    public string $commandDescription = 'Create a shop from the public signup flow.';

    public function handle(
        BillingPlan $plan,
        string $name,
        string $domain,
        string $timezone,
        string $currency,
        string $managerName,
        string $managerEmail,
        string $siteLocale = Locales::Default,
        ?string $addressLine1 = null,
        ?string $addressLine2 = null,
        ?string $city = null,
        ?string $administrativeArea = null,
        ?string $postalCode = null,
        ?string $countryCode = null,
    ): Tenant {
        return app(CreateTenantAction::class)(
            $name,
            $domain,
            $timezone,
            $currency,
            $managerName,
            $managerEmail,
            $plan,
            $siteLocale,
            $addressLine1,
            $addressLine2,
            $city,
            $administrativeArea,
            $postalCode,
            $countryCode,
        );
    }

    public function asController(Request $request): RedirectResponse
    {
        $centralDomain = config('tenancy.central_domains.0', 'localhost');
        $submitted = $request->all();
        $submitted['domain'] = Str::lower((string) $request->input('shop_slug').'.'.$centralDomain);
        $submitted['timezone'] ??= 'America/New_York';
        $submitted['currency'] ??= 'usd';

        $validator = Validator::make($submitted, $this->rules());
        $validator->after(function ($validator) use ($submitted): void {
            if (Domain::query()->where('domain', $submitted['domain'])->exists()) {
                $validator->errors()->add('shop_slug', 'That shop address is already taken.');
            }
        });
        $data = $validator->validate();
        $plan = BillingPlan::query()->where('slug', $data['plan'])->where('is_public', true)->firstOrFail();
        $tenant = $this->handle(
            $plan,
            $data['name'],
            $submitted['domain'],
            $data['timezone'],
            $data['currency'],
            $data['manager_name'],
            $data['manager_email'],
            Locales::canonical($request->attributes->get('embiken.locale')),
            $data['address_line_1'] ?? null,
            $data['address_line_2'] ?? null,
            $data['city'] ?? null,
            $data['administrative_area'] ?? null,
            $data['postal_code'] ?? null,
            isset($data['country_code']) ? strtoupper($data['country_code']) : null,
        );

        return redirect()->route('marketing.checkout.placeholder')->with([
            'signup_plan' => $plan->name,
            'signup_domain' => $tenant->domains()->value('domain'),
            'signup_email' => $tenant->manager_email,
        ]);
    }

    public function asCommand(Command $command): int
    {
        $value = fn (string $option, string $argument, ?string $default = null): string => $this->requiredStringInput(
            $command,
            $command->argument($argument) ?? $command->option($option),
            str_replace('-', ' ', $argument),
            $default,
        );

        $plan = BillingPlan::query()->whereKey($command->option('plan') ?: 'starter')->firstOrFail();
        $tenant = $this->handle(
            $plan,
            $value('name', 'name'),
            $value('domain', 'domain'),
            $value('timezone', 'timezone', 'America/New_York'),
            $value('currency', 'currency', 'usd'),
            $value('manager-name', 'manager-name'),
            $value('manager-email', 'manager-email'),
            Locales::Default,
            $this->optionalStringValue($command->option('address-line-1'), 'Address line 1'),
            $this->optionalStringValue($command->option('address-line-2'), 'Address line 2'),
            $this->optionalStringValue($command->option('city'), 'City'),
            $this->optionalStringValue($command->option('administrative-area'), 'State or region'),
            $this->optionalStringValue($command->option('postal-code'), 'Postal code'),
            $this->optionalStringValue($command->option('country-code'), 'Country code'),
        );
        $command->info("Created tenant {$tenant->id} on {$plan->name}.");

        return self::SUCCESS;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'shop_slug' => ['required', 'string', 'min:2', 'max:60', 'regex:/^[a-z0-9](?:[a-z0-9-]*[a-z0-9])?$/'],
            'timezone' => ['required', 'timezone'],
            'currency' => ['required', 'string', 'regex:/^[A-Za-z]{3}$/'],
            'manager_name' => ['required', 'string', 'max:255'],
            'manager_email' => ['required', 'email', 'max:255'],
            'address_line_1' => ['nullable', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'administrative_area' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:32'],
            'country_code' => ['nullable', 'string', 'size:2', 'alpha'],
            'plan' => ['required', 'string', Rule::exists('billing_plans', 'slug')->where('is_public', true)->where('custom_pricing', false)],
        ];
    }
}
