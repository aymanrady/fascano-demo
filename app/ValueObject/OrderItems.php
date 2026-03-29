<?php

declare(strict_types=1);

namespace App\ValueObject;

use App\Models\Menu\Item;
use Illuminate\Support\Collection;

/**
 * @extends Collection<int, OrderItem>
 */
final class OrderItems extends Collection
{
    public function addItem(Item $item): void
    {
        $orderItem = $this->get($item->id) ?? new OrderItem(
            itemId: $item->id,
            quantity: 0,
            unitPrice: $item->price,
        );

        $this->put($item->id, $orderItem->increment());
    }

    public function removeItem(Item $item): void
    {
        $orderItem = $this->get($item->id);

        if (! $orderItem instanceof OrderItem) {
            return;
        }

        $this->put($item->id, $orderItem = $orderItem->decrement());

        if ($orderItem->quantity === 0) {
            $this->forget($item->id);
        }
    }
}
