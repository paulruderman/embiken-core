<?php

namespace App\Actions\Shop;

use App\Actions\Action;
use App\Enums\StaffRole;
use App\Models\ShopSetting;
use App\Models\Staff;
use App\Support\BrandColor;
use Closure;
use Illuminate\Console\Command;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class UpdateShopBrandingAction extends Action
{
    public string $commandSignature = 'shop:branding {--tenant=} {--brand-name=} {--primary-color=} {--brand-gray-color=} {--logo-path=} {--favicon-path=}';

    /**
     * @param  array{brand_name: string|null, primary_color: string|null, brand_gray_color: string|null, logo_path: string|null, favicon_path: string|null}  $branding
     */
    public function handle(array $branding): ShopSetting
    {
        validator($branding, $this->rules())->validate();
        $this->validateAssetPath($branding['logo_path']);
        $this->validateAssetPath($branding['favicon_path']);

        $settings = ShopSetting::current();
        $settings->fill($branding);
        $settings->save();

        return $settings;
    }

    public function asController(Request $request): JsonResponse
    {
        abort_unless(tenant() !== null, 404);

        $staff = Auth::guard('staff')->user();
        abort_unless($staff instanceof Staff && $staff->role === StaffRole::Manager, 403);

        $branding = $request->validate($this->rules());

        return response()->json($this->handle($branding));
    }

    public function asCommand(Command $command): int
    {
        $this->initializeTenancyFromCommand($command);

        $current = ShopSetting::current();
        $options = [
            'brand_name' => $command->option('brand-name') ?? \Laravel\Prompts\text(
                'Brand name (blank uses the shop name)',
                default: $current->brand_name ?? '',
            ),
            'primary_color' => $command->option('primary-color') ?? \Laravel\Prompts\text(
                'Primary Tailwind color class or HEX value (blank uses the default)',
                default: $current->primary_color ?? '',
            ),
            'brand_gray_color' => $command->option('brand-gray-color') ?? \Laravel\Prompts\text(
                'Brand gray Tailwind color class or HEX value (blank uses the default)',
                default: $current->brand_gray_color ?? '',
            ),
            'logo_path' => $command->option('logo-path') ?? \Laravel\Prompts\text(
                'Logo path (blank removes the custom logo)',
                default: $current->logo_path ?? '',
            ),
            'favicon_path' => $command->option('favicon-path') ?? \Laravel\Prompts\text(
                'Favicon path (blank removes the custom favicon)',
                default: $current->favicon_path ?? '',
            ),
        ];

        $branding = array_map(static fn (mixed $value): ?string => filled($value) ? (string) $value : null, $options);
        validator($branding, $this->rules())->validate();
        $this->handle($branding);

        $command->info('Shop branding updated.');

        return self::SUCCESS;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function rules(): array
    {
        $colorRule = static function (string $attribute, mixed $value, Closure $fail): void {
            if (! BrandColor::isValid($value)) {
                $fail('Enter a Tailwind color family name or a six-digit HEX value.');
            }
        };

        return [
            'brand_name' => ['present', 'nullable', 'string', 'max:255'],
            'primary_color' => ['present', 'nullable', 'string', 'max:32', $colorRule],
            'brand_gray_color' => ['present', 'nullable', 'string', 'max:32', $colorRule],
            'logo_path' => ['present', 'nullable', 'string', 'max:1024', 'regex:/\Abranding\/[A-Za-z0-9][A-Za-z0-9._-]*\.(?:png|jpe?g|webp)\z/i'],
            'favicon_path' => ['present', 'nullable', 'string', 'max:1024', 'regex:/\Abranding\/[A-Za-z0-9][A-Za-z0-9._-]*\.png\z/i'],
        ];
    }

    private function validateAssetPath(?string $path): void
    {
        if ($path !== null && ! Storage::disk('public')->exists($path)) {
            throw ValidationException::withMessages([
                'branding' => 'The selected branding file could not be found.',
            ]);
        }
    }
}
