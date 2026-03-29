<?php

declare(strict_types=1);

namespace App\ValueObject;

use Illuminate\Support\Str;
use JsonSerializable;

final class CreditCard implements JsonSerializable
{
    public string $numberFormatted {
        get => substr($this->number, 0, 4)
            .' **** ***'
            .substr($this->number, 11, 1)
            .' '
            .substr($this->number, 12);
    }

    public function __construct(
        public readonly string $number,
        public readonly string $cardHolder,
        public readonly string $expirationDate,
        public readonly string $cvv,
    ) {}

    public function mask(): self
    {
        return new self(
            number: Str::mask($this->number, '*', 4, 7),
            cardHolder: $this->cardHolder,
            expirationDate: $this->expirationDate,
            cvv: Str::mask($this->cvv, '*', 0),
        );
    }

    public function jsonSerialize(): mixed
    {
        return [
            'number' => $this->number,
            'card_holder' => $this->cardHolder,
            'expiration_date' => $this->expirationDate,
            'cvv' => $this->cvv,
        ];
    }
}
