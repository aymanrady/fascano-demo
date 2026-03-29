<?php

use App\Models\Order;
use App\Rules\CreditCardNumber;
use App\Services\PaymentGateway;
use App\ValueObject\CreditCard;
use chillerlan\QRCode\QRCode;
use Cknow\Money\Money;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Layout('layouts::frontend')]
class extends Component {
    public Order $order;

    #[Validate(['required', new CreditCardNumber])]
    public string $creditCardNumber;

    #[Validate('required')]
    public string $cardHolder;

    #[Validate('required|date_format:m/y|after:now')]
    public string $expirationDate;

    #[Validate('required|numeric|digits:3')]
    public string $cvc;

    public bool $splitPayment = false;

    #[Validate('required_if:splitPayment,true|gte:2')]
    public int $splitPaymentNumber = 2;

    private PaymentGateway $gateway;

    public function boot(PaymentGateway $gateway): void
    {
        $this->gateway = $gateway;
    }

    public function pay(): void
    {
        $this->validate();

        $this->gateway->pay(
            $this->order,
            $this->amountToPay,
            new CreditCard(
                number: Str::numbers($this->creditCardNumber),
                cardHolder: $this->cardHolder,
                expirationDate: $this->expirationDate,
                cvv: $this->cvc,
            )
        );

        $this->order = $this->order->fresh();
    }

    #[Computed]
    public function amountToPay(): Money
    {
        if ($this->splitPayment) {
            return Money::min(
                array_first(
                    $this->order->total->allocateTo($this->splitPaymentNumber ?? 1)
                ),
                $this->order->remainder,
            );
        }

        return $this->order->remainder;
    }
};
?>

<div class="space-y-6 max-w-md mx-auto">
    <flux:card class="space-y-6">
        <div>
            <flux:heading>Order Review</flux:heading>
            <flux:text class="mt-2">Please review your order before proceeding.</flux:text>
        </div>
        <div class="space-y-2">
            @forelse($order->items as $orderItem)
                <div class="flex">
                    <flux:heading>x{{ $orderItem->quantity }} {{ $orderItem->name }}</flux:heading>
                    <flux:spacer/>
                    <flux:heading>{{ $orderItem->total }}</flux:heading>
                </div>
            @empty
                <flux:heading>No items in your order</flux:heading>
            @endforelse
        </div>

        <flux:separator/>

        <div class="flex">
            <flux:heading>Total</flux:heading>
            <flux:spacer/>
            <flux:heading>{{ $order->total }}</flux:heading>
        </div>

        @unless($order->isLocked())
            <flux:button :href="route('app.menu', ['order' => $order])" class="w-full" icon="shopping-cart">
                Add more items
            </flux:button>
        @endunless
    </flux:card>

    @if($order->isPaid())
        <flux:card class="space-y-6">
            <div>
                <flux:heading>Order Paid</flux:heading>
                <flux:text>Your order will be served shortly</flux:text>
            </div>
        </flux:card>
    @endif

    @if($order->hasPayments())
        <flux:card>
            <flux:heading>Payments</flux:heading>
            @foreach($order->payments()->successful()->get() as $payment)
                <div class="flex justify-between mt-4" wire:key="{{ $payment->id }}">
                    <div>
                        <flux:heading size="lg">{{ $payment->credit_card->numberFormatted }}</flux:heading>
                        <flux:text>{{ $payment->credit_card->cardHolder }}</flux:text>
                    </div>
                    <flux:heading size="xl">{{ $payment->amount }}</flux:heading>
                </div>
            @endforeach
        </flux:card>
    @endif

    @if($order->needsPayment())
        <form wire:submit="pay">
            <flux:card class="space-y-6">
                <flux:field>
                    <flux:input icon:trailing="credit-card" placeholder="4444 4444 4444 4444" mask="9999 9999 9999 9999"
                                wire:model="creditCardNumber"/>
                    <flux:error name="creditCardNumber"/>
                </flux:field>

                <flux:field>
                    <flux:input icon:trailing="user" placeholder="John Doe" wire:model="cardHolder"/>
                    <flux:error name="cardHolder"/>
                </flux:field>

                <div class="flex space-x-2">
                    <flux:field>
                        <flux:input icon:trailing="calendar" placeholder="MM/YY" mask="99/99"
                                    wire:model="expirationDate"/>
                        <flux:error name="expirationDate"/>
                    </flux:field>

                    <flux:field>
                        <flux:input type="password" placeholder="CVC" wire:model="cvc" autocomplete="csc" maxlength="3"
                                    minlength="3"/>
                        <flux:error name="cvc"/>
                    </flux:field>
                </div>

                <flux:switch wire:model.live="splitPayment" label="Split payment"/>

                @if($splitPayment)
                    <div class="space-y-4">
                        <flux:field>
                            <flux:input type="number" placeholder="Number of splits"
                                        wire:model.live="splitPaymentNumber" min="2" autocomplete="off"/>
                            <flux:error name="splitPaymentNumber"/>
                        </flux:field>
                    </div>
                @endif

                <flux:button type="submit" class="w-full">Pay {{ $this->amountToPay }}</flux:button>
            </flux:card>
        </form>
    @endif

    @if($order->isPartiallyPaid())
        <flux:card class="space-y-6">
            <flux:heading>Payment Link</flux:heading>
            <flux:text>Share this link with your friends to complete the payment.</flux:text>
            <flux:input :value="route('app.pay', ['order' => $order])" class="mt-4" readonly copyable/>
            <flux:separator text="or"/>
            <flux:text class="text-center">Scan</flux:text>
            <img src="{{ new QRCode()->render(route('app.pay', ['order' => $order])) }}"/>
        </flux:card>
    @endif
</div>
