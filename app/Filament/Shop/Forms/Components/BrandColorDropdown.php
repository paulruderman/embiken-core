<?php

namespace App\Filament\Shop\Forms\Components;

use App\Support\BrandColor;
use Closure;
use Filament\Forms\Components\TextInput;
use Illuminate\Validation\ClosureValidationRule;

class BrandColorDropdown extends TextInput
{
    protected string $view = 'filament.shop.forms.components.brand-color-dropdown';

    protected function setUp(): void
    {
        parent::setUp();

        $this->rule(new ClosureValidationRule(static function (string $attribute, mixed $value, Closure $fail): void {
            if (! BrandColor::isValid($value)) {
                $fail(__('app.branding_ui.invalid_color'));
            }
        }));
    }
}
