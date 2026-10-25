<?php

namespace App\Actions\Shop;

use App\Actions\Action;
use App\Enums\StaffRole;
use App\Http\Resources\ShopLandingPageResource;
use App\Models\ShopLandingPage;
use App\Models\Staff;
use App\Support\Locales;
use Illuminate\Console\Command;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use JsonException;

class SaveShopLandingPageAction extends Action
{
    public string $commandSignature = 'shop:landing-page {--tenant=} {--page-json=}';

    /** @param array<string, mixed> $page */
    public function handle(array $page): ShopLandingPage
    {
        $unexpectedKeys = array_diff(array_keys($page), ['layout', 'layout_data', 'theme', 'custom_content', 'booking_notices']);

        if ($unexpectedKeys !== []) {
            throw ValidationException::withMessages([
                (string) reset($unexpectedKeys) => 'This field is not accepted.',
            ]);
        }

        validator($page, $this->rulesForPage($page))->validate();

        $imagePath = $page['layout_data']['shared']['image_path'] ?? null;

        if (is_string($imagePath) && ! Storage::disk('public')->exists($imagePath)) {
            throw ValidationException::withMessages([
                'layout_data.shared.image_path' => 'The selected image could not be found.',
            ]);
        }

        return ShopLandingPage::query()->updateOrCreate(
            ['id' => ShopLandingPage::SINGLETON_ID],
            $page,
        );
    }

    public function asController(Request $request): ShopLandingPage
    {
        abort_unless(tenant() !== null, 404);

        $staff = Auth::guard('staff')->user();
        abort_unless($staff instanceof Staff && $staff->role === StaffRole::Manager, 403);

        $page = $request->all();
        validator($page, $this->rulesForPage($page))->validate();

        return $this->handle($page);
    }

    public function jsonResponse(ShopLandingPage $page): JsonResponse
    {
        return response()->json(new ShopLandingPageResource($page));
    }

    public function asCommand(Command $command): int
    {
        $this->initializeTenancyFromCommand($command);
        $current = ShopLandingPage::current();
        $default = $current?->only(['layout', 'layout_data', 'theme', 'custom_content', 'booking_notices']) ?? [
            'layout' => 'hero',
            'layout_data' => [
                'shared' => ['image_path' => null, 'image_position' => 'right'],
                'en' => ['eyebrow' => '', 'headline' => '', 'description' => '', 'button_label' => 'Book a bike'],
                'es' => ['eyebrow' => '', 'headline' => '', 'description' => '', 'button_label' => 'Reservar una bicicleta'],
                'fr' => ['eyebrow' => '', 'headline' => '', 'description' => '', 'button_label' => 'Réserver un vélo'],
            ],
            'theme' => 'light',
            'custom_content' => null,
            'booking_notices' => [],
        ];
        $encodedDefault = json_encode($default, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
        $pageJson = $command->option('page-json');

        if ($pageJson === null) {
            if ($command->option('no-interaction')) {
                throw ValidationException::withMessages(['page-json' => 'The --page-json option is required.']);
            }

            $pageJson = \Laravel\Prompts\textarea('Shop home page JSON', default: $encodedDefault);
        }

        if (! is_string($pageJson)) {
            throw ValidationException::withMessages(['page-json' => 'The shop home page JSON must be a string.']);
        }

        try {
            $page = json_decode($pageJson, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw ValidationException::withMessages(['page-json' => 'Enter valid JSON for the shop home page.']);
        }

        if (! is_array($page)) {
            throw ValidationException::withMessages(['page-json' => 'The shop home page JSON must be an object.']);
        }

        $this->handle($page);
        $command->info('Shop home page updated.');

        return self::SUCCESS;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'layout' => ['required', 'string', 'in:hero,split,story'],
            'layout_data' => ['required', 'array'],
            'theme' => ['required', 'string', 'in:light,warm,dark'],
            'custom_content' => ['nullable'],
            'booking_notices' => ['nullable', 'array', 'max:5'],
        ];
    }

    /** @param array<string, mixed> $page
     * @return array<string, array<int, mixed>>
     */
    private function rulesForPage(array $page): array
    {
        $layout = $page['layout'] ?? null;
        $locales = Locales::tenantContentLocales();
        $defaultLocale = Locales::tenantDefaultContentLocale();
        $localeKeys = implode(',', Locales::contentLocales());
        $translatedKeys = match ($layout) {
            'story' => ['eyebrow', 'headline', 'description', 'button_label', 'story_quote', 'story_byline'],
            default => ['eyebrow', 'headline', 'description', 'button_label'],
        };
        $rules = [
            'layout' => ['required', 'string', 'in:hero,split,story'],
            'layout_data' => ['required', 'array:shared,'.$localeKeys],
            'layout_data.shared' => ['required', 'array:image_path,image_position'],
            'layout_data.shared.image_path' => ['nullable', 'string', 'max:1024', 'regex:/\Alanding-pages\/[A-Za-z0-9][A-Za-z0-9._-]*\.(?:png|jpe?g|webp)\z/i'],
            'layout_data.shared.image_position' => ['required_if:layout,split', 'nullable', 'in:left,right'],
            'theme' => ['required', 'string', 'in:light,warm,dark'],
            'custom_content' => ['nullable', 'array:'.$localeKeys],
            'booking_notices' => ['nullable', 'array', 'max:5'],
            'booking_notices.*.tone' => ['required', 'string', 'in:info,success,warning'],
        ];

        foreach ($locales as $locale) {
            $rules["layout_data.{$locale}"] = ['required', 'array:'.implode(',', $translatedKeys)];
            foreach ($translatedKeys as $key) {
                $required = $locale === $defaultLocale && in_array($key, ['headline', 'description', 'button_label'], true);
                $max = match ($key) {
                    'eyebrow' => 120,
                    'headline' => 180,
                    'description' => 1200,
                    'button_label' => 80,
                    'story_quote' => 500,
                    'story_byline' => 120,
                };
                $rules["layout_data.{$locale}.{$key}"] = [$required ? 'required' : 'nullable', 'string', "max:{$max}"];
            }
            $rules["custom_content.{$locale}"] = ['nullable'];
            $rules["booking_notices.*.message.{$locale}"] = [$locale === $defaultLocale ? 'required' : 'nullable', 'string', 'max:240'];
        }

        $rules['booking_notices.*'] = ['required', 'array:message,tone'];
        $rules['booking_notices.*.message'] = ['required', 'array:'.$localeKeys];

        return $rules;
    }
}
