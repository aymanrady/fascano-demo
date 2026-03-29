<?php

namespace App\Enums;

enum Permission: string
{
    case ViewRestaurants = 'view restaurants';
    case ViewOthersRestaurants = 'view others restaurants';
}
