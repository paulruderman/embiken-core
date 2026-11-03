<?php

namespace App\Filament\Tables\Columns;

use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;

class NegativeBooleanIconColumn extends IconColumn
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->boolean()
            ->trueIcon(Heroicon::OutlinedXCircle)
            ->falseIcon(Heroicon::OutlinedMinusCircle)
            ->trueColor('danger')
            ->falseColor('gray');
    }
}
