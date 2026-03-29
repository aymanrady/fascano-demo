<?php

namespace App\Casts;

use App\ValueObject\OrderTotals;
use Cknow\Money\Money;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class AsOrderTotals implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): OrderTotals
    {
        $totals = json_decode($value, true);

        return new OrderTotals(
            subtotal: Money::parse($totals['subtotal']['amount'] ?? 0, $totals['subtotal']['currency'] ?? null),
            tip: Money::parse($totals['tip']['amount'] ?? 0, $totals['tip']['currency'] ?? null),
        );
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
