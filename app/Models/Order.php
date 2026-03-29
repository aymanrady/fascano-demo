<?php

namespace App\Models;

use App\Casts\AsOrderItems;
use App\Casts\AsOrderTotals;
use App\Enums\OrderStatus;
use App\Models\Menu\Item;
use App\ValueObject\OrderItem;
use App\ValueObject\OrderItems;
use App\ValueObject\OrderTotals;
use Cknow\Money\Money;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

#[Fillable(['table_id', 'status'])]
class Order extends Model
{
    protected $attributes = [
        'status' => OrderStatus::Pending,
        'items' => '[]',
        'totals' => '[]',
    ];

    public function add(Item $item): void
    {
        if ($this->status !== OrderStatus::Pending) {
            return;
        }

        $this->items->addItem($item);
        $this->totals = $this->totals->withItems($this->items);
    }

    public function remove(Item $item): void
    {
        if ($this->status !== OrderStatus::Pending) {
            return;
        }

        $this->items->removeItem($item);
        $this->totals = $this->totals->withItems($this->items);
    }

    public function addTip(Money $tip): void
    {
        if ($this->status !== OrderStatus::Pending) {
            return;
        }

        $this->totals = $this->totals->withTip($tip);
    }

    public function needsPayment(): bool
    {
        return $this->remainder->isPositive();
    }

    public function isPaid(): bool
    {
        return $this->total->isPositive()
            && $this->total->lessThanOrEqual($this->totalPaid);
    }

    public function isPartiallyPaid(): bool
    {
        return $this->totalPaid->isPositive()
            && $this->total->greaterThan($this->totalPaid);
    }

    public function hasPayments(): bool
    {
        return $this->payments()->exists();
    }

    public function isLocked(): bool
    {
        return $this->hasPayments();
    }

    protected function total(): Attribute
    {
        return Attribute::get(
            fn () => $this->totals->total
        );
    }

    protected function remainder(): Attribute
    {
        return Attribute::get(
            fn () => $this->total->subtract($this->total_paid)
        );
    }

    protected function totalPaid(): Attribute
    {
        return Attribute::get(
            fn () => $this->payments()
                ->successful()
                ->get()
                ->reduce(
                    fn (Money $totalPaid, Payment $payment) => $totalPaid->add($payment->amount),
                    Money::parse(0, $this->total->getCurrency())
                )
        );
    }

    protected function unpaidItems(): Attribute
    {
        return Attribute::get(function() {
            $paidItems = $this->payments()
                ->successful()
                ->get()
                ->map(fn(Payment $payment) => $payment->items)
                ->reduce(
                    function (Collection $paidItems, Collection $paymentItems) {
                        $paymentItems->each(
                            fn(int $quantity, int $itemId) => $paidItems->put(
                                $itemId, $paidItems->get($itemId, 0) + $quantity
                            )
                        );

                        return $paidItems;
                    },
                    Collection::make()
                );

            return new OrderItems(
                $this->items
                    ->map(fn(OrderItem $item) => new OrderItem(
                        itemId: $item->itemId,
                        quantity: $item->quantity - $paidItems->get($item->itemId, 0),
                        unitPrice: $item->unitPrice,
                    ))
                    ->filter(fn(OrderItem $item) => $item->quantity > 0)
            );
        });
    }

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'items' => AsOrderItems::class,
            'totals' => AsOrderTotals::class,
        ];
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(Table::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    #[Scope]
    protected function pending(Builder $query): void
    {
        $query->where('status', OrderStatus::Pending);
    }

    #[Scope]
    protected function processing(Builder $query): void
    {
        $query->where('status', OrderStatus::Processing);
    }

    #[Scope]
    protected function completed(Builder $query): void
    {
        $query->where('status', OrderStatus::Completed);
    }
}
