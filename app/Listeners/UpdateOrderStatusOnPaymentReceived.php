<?php

namespace App\Listeners;

use App\Enums\OrderStatus;
use App\Events\PaymentReceived;

class UpdateOrderStatusOnPaymentReceived
{
    public function handle(PaymentReceived $event): void
    {
        if ($event->payment->order->isPaid()) {
            $event->payment->order->update(['status' => OrderStatus::Processing]);
            $event->payment->order->save();
        }
    }
}
