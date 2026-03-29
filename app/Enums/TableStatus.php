<?php

namespace App\Enums;

enum TableStatus: string
{
    case Open = 'open';
    case Occupied = 'occupied';
    case Waiting = 'waiting';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::Occupied => 'Occupied',
            self::Waiting => 'Waiting',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Open => 'green',
            self::Occupied => 'yellow',
            self::Waiting => 'red',
        };
    }
}
