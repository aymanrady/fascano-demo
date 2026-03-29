<?php

namespace App\Casts;

use App\ValueObject\CreditCard;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class AsCreditCard implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): CreditCard
    {
        $card = json_decode($value, true);

        return new CreditCard(
            number: $card['number'],
            cardHolder: $card['card_holder'],
            expirationDate: $card['expiration_date'],
            cvv: $card['cvv'],
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
