<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Events\PaymentReceived;
use App\Models\Order;
use App\ValueObject\CreditCard;
use Cknow\Money\Money;
use LogicException;

final readonly class PaymentGateway
{
    public function pay(Order $order, Money $amount, CreditCard $card): void
    {
        if ($amount->greaterThan($order->total)) {
            throw new LogicException('The amount exceeds the order total.');
        }

        $payment = $order->payments()->create([
            'amount' => $amount,
            'status' => PaymentStatus::Successful,
            'credit_card' => $card->mask(),
        ]);

        PaymentReceived::dispatch($payment);
    }
}
