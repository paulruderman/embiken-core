<?php

namespace App\Exceptions;

use RuntimeException;

class OutBikesNeedConfirmation extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Out bikes are still on this reservation. Confirm they are in the shop, then cancel.');
    }
}
