<?php

declare(strict_types=1);

namespace App\ValueObject;

use Cknow\Money\Money;
use JsonSerializable;

final readonly class OrderTotals implements JsonSerializable
{
    public static function fromItems(OrderItems $items): self
    {
        $total = $items->reduce(
            fn (Money $total, OrderItem $orderItem) => $total->add($orderItem->unitPrice->multiply($orderItem->quantity)),
            Money::parse(0)
        );

        return new self($total);
    }

    public function __construct(
        public Money $total
    ) {}

    public function jsonSerialize(): array
    {
        return [
            'total' => [
                'amount' => $this->total->getAmount(),
                'currency' => $this->total->getCurrency(),
            ],
        ];
    }
}
