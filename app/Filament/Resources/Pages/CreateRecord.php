<?php

namespace App\Filament\Resources\Pages;

use Filament\Resources\Pages\CreateRecord as FilamentCreateRecord;

abstract class CreateRecord extends FilamentCreateRecord
{
    protected static bool $canCreateAnother = false;
}
