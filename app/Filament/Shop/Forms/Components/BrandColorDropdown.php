<?php

namespace App\Filament\Shop\Forms\Components;

use App\Support\BrandColor;
use Closure;
use Filament\Forms\Components\TextInput;

class BrandColorDropdown extends TextInput
{
    protected string $view = 'filament.shop.forms.components.brand-color-dropdown';

    protected function setUp(): void
    {
        parent::setUp();

        $this->rule(static function (string $attribute, mixed $value, Closure $fail): void {
            if (! BrandColor::isValid($value)) {
                $fail('Enter a Tailwind color family name or a six-digit HEX value.');
            }
        });
    }
}
