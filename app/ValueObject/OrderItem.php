<?php

declare(strict_types=1);

namespace App\ValueObject;

use App\Models\Menu\Item;
use Cknow\Money\Money;
use JsonSerializable;

final class OrderItem implements JsonSerializable
{
    public string $name {
        get => $this->name ??= Item::findSole($this->itemId)->name;
    }

    public Money $total {
        get => $this->unitPrice->multiply($this->quantity);
    }

    public function __construct(
        public readonly int $itemId,
        public readonly int $quantity,
        public readonly Money $unitPrice,
    ) {}

    public function increment(): self
    {
        return new self(
            itemId: $this->itemId,
            quantity: $this->quantity + 1,
            unitPrice: $this->unitPrice,
        );
    }

    public function decrement(int $qty = 1): self
    {
        return new self(
            itemId: $this->itemId,
            quantity: max(0, $this->quantity - $qty),
            unitPrice: $this->unitPrice,
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'item_id' => $this->itemId,
            'quantity' => $this->quantity,
            'unit_price' => [
                'amount' => $this->unitPrice->getAmount(),
                'currency' => $this->unitPrice->getCurrency(),
            ],
        ];
    }
}
