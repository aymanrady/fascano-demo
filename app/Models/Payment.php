<?php

namespace App\Models;

use App\Casts\AsCreditCard;
use App\Enums\PaymentStatus;
use Cknow\Money\Casts\MoneyIntegerCast;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['amount', 'status', 'credit_card'])]
class Payment extends Model
{
    protected function casts(): array
    {
        return [
            'amount' => MoneyIntegerCast::class.':currency',
            'status' => PaymentStatus::class,
            'credit_card' => AsCreditCard::class,
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    #[Scope]
    protected function successful(Builder $query): void
    {
        $query->where('status', PaymentStatus::Successful);
    }
}
