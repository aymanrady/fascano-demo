<?php

namespace App\Models;

use App\Casts\AsCreditCard;
use App\Enums\PaymentStatus;
use Cknow\Money\Casts\MoneyIntegerCast;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

#[Fillable(['amount', 'status', 'credit_card', 'metadata'])]
class Payment extends Model
{
    protected $attributes = [
        'metadata' => '[]'
    ];

    protected function casts(): array
    {
        return [
            'amount' => MoneyIntegerCast::class.':currency',
            'status' => PaymentStatus::class,
            'credit_card' => AsCreditCard::class,
            'metadata' => AsCollection::class,
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    protected function items(): Attribute
    {
        return Attribute::get(
            fn() => Collection::make($this->metadata->get('items') ?? [])
        );
    }

    #[Scope]
    protected function successful(Builder $query): void
    {
        $query->where('status', PaymentStatus::Successful);
    }
}
