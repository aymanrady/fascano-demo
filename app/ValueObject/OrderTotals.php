<?php

declare(strict_types=1);

namespace App\ValueObject;

use Cknow\Money\Money;
use JsonSerializable;

final class OrderTotals implements JsonSerializable
{
    public Money $total {
        get => $this->subtotal->add($this->tip);
    }

    public function __construct(
        public readonly Money $subtotal,
        public readonly Money $tip,
    ) {}

    public function withItems(OrderItems $items): self
    {
        return new self(
            subtotal: $items->reduce(
                fn (Money $total, OrderItem $orderItem) => $total->add($orderItem->unitPrice->multiply($orderItem->quantity)),
                Money::parse(0)
            ),
            tip: $this->tip,
        );
    }

    public function withTip(Money $tip): self
    {
        return new self(
            subtotal: $this->subtotal,
            tip: $tip,
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'subtotal' => [
                'amount' => $this->subtotal->getAmount(),
                'currency' => $this->subtotal->getCurrency(),
            ],
            'tip' => [
                'amount' => $this->tip->getAmount(),
                'currency' => $this->tip->getCurrency(),
            ]
        ];
    }
}
