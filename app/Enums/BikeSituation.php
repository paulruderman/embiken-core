<?php

namespace App\Enums;

enum BikeSituation: string
{
    case Home = 'home';
    case Prepping = 'prepping';
    case Staged = 'staged';
    case RentedOut = 'rented_out';
    case Back = 'back';
}
