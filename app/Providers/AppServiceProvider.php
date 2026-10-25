<?php

namespace App\Providers;

use App\Models\Staff;
use App\Models\User;
use App\Support\Locales;
use BezhanSalleh\LanguageSwitch\Events\LocaleChanged;
use BezhanSalleh\LanguageSwitch\LanguageSwitch;
use Carbon\CarbonImmutable;
use Filament\Facades\Filament;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Spatie\Translatable\Facades\Translatable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (app()->runningInConsole()) {
            $centralDomain = config('tenancy.central_domains.0');

            if (is_string($centralDomain) && $centralDomain !== '') {
                Filament::currentDomain($centralDomain);
            }
        }

        $this->configureDefaults();
        Table::configureUsing(static function (Table $table): void {
            $table->striped();
        });

        Translatable::fallback('en');

        LanguageSwitch::configureUsing(function (LanguageSwitch $switch): void {
            $switch
                ->locales(Locales::all())
                ->labels(Locales::labels())
                ->userPreferredLocale(function (): ?string {
                    $guard = Filament::getCurrentPanel()?->getAuthGuard();

                    return $guard === null ? null : Auth::guard($guard)->user()?->locale;
                });
        });

        Event::listen(LocaleChanged::class, function (LocaleChanged $event): void {
            $guard = Filament::getCurrentPanel()?->getAuthGuard();
            $user = $guard === null ? null : Auth::guard($guard)->user();

            if (($user instanceof User || $user instanceof Staff) && Locales::isSupported($event->locale)) {
                $user->forceFill(['locale' => Locales::canonical($event->locale)])->save();
            }
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
