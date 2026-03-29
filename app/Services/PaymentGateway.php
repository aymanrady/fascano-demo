<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Events\PaymentReceived;
use App\Models\Order;
use App\ValueObject\CreditCard;
use Cknow\Money\Money;
use Illuminate\Support\Collection;
use LogicException;

final readonly class PaymentGateway
{
    public function pay(Order $order, Money $amount, CreditCard $card, ?Collection $items = null): void
    {
        if ($amount->greaterThan($order->total)) {
            throw new LogicException('The amount exceeds the order total.');
        }

        $payment = $order->payments()->create([
            'amount' => $amount,
            'status' => PaymentStatus::Successful,
            'credit_card' => $card->mask(),
            'metadata' => new Collection([
                'items' => $items?->toArray(),
            ])
        ]);

        PaymentReceived::dispatch($payment);
    }
}
