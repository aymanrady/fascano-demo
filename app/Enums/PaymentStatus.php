<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Successful = 'successful';
    case Failed = 'failed';
}
