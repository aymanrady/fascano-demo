<?php

namespace App\Casts;

use App\ValueObject\OrderItem;
use App\ValueObject\OrderItems;
use Cknow\Money\Money;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class AsOrderItems implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): OrderItems
    {
        return new OrderItems(json_decode($value, true))
            ->map(fn ($item) => new OrderItem(
                itemId: $item['item_id'],
                quantity: $item['quantity'],
                unitPrice: Money::parse($item['unit_price']['amount'], $item['unit_price']['currency']),
            ));
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        return json_encode($value);
    }
}
