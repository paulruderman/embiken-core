<?php

namespace App\Actions\Platform;

use App\Actions\Action;
use App\Models\BillingPlan;
use App\Models\Tenant;
use App\Support\ModuleRegistry;
use Illuminate\Console\Command;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CreateTenantAction extends Action
{
    public string $commandSignature = 'tenants:create {name?} {--domain=} {--timezone=America/New_York} {--currency=usd} {--manager-name=} {--manager-email=} {--plan=starter}';

    public string $commandDescription = 'Provision a shop Tenant, Location, Manager invite, and Platform Manager.';

    public function handle(
        string $name,
        string $domain,
        string $timezone,
        string $currency,
        string $managerName,
        string $managerEmail,
        ?BillingPlan $billingPlan = null,
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

        $tenant->run(function () use ($name, $timezone, $currency, $managerName, $managerEmail): void {
            app(ProvisionShopAction::class)(
                shopName: $name,
                timezone: $timezone,
                currency: $currency,
                managerName: $managerName,
                managerEmail: $managerEmail,
            );
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

        $tenant = $this->handle($name, $domain, $timezone, $currency, $managerName, $managerEmail, $plan);
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
            'currency' => ['required', 'string', 'size:3'],
            'manager_name' => ['required', 'string', 'max:255'],
            'manager_email' => ['required', 'email'],
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
