<?php

use App\Models\Menu\Item;
use App\Models\Order;
use App\Rules\CreditCardNumber;
use App\Services\PaymentGateway;
use App\ValueObject\CreditCard;
use App\ValueObject\OrderItem;
use chillerlan\QRCode\QRCode;
use Cknow\Money\Money;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Attributes\Url;
use Livewire\Component;
use Money\Currency;

new #[Layout('layouts::frontend')]
class extends Component {
    public Order $order;

    public bool $addTip = false;

    #[Validate('required_if:addTip,true|numeric')]
    public string $tip = '10';

    #[Validate(['required', new CreditCardNumber])]
    public string $creditCardNumber;

    #[Validate('required')]
    public string $cardHolder;

    #[Validate('required|date_format:m/y|after:now')]
    public string $expirationDate;

    #[Validate('required|numeric|digits:3')]
    public string $cvc;

    #[Url(as: 'split')]
    public bool $splitPayment = false;

    public Collection $itemsToPay;

    public string $splitAmountInput = '';

    private PaymentGateway $gateway;

    public function mount(): void
    {
        $this->itemsToPay = new Collection;

        if ($this->order->totals->tip->isPositive()) {
            $this->addTip = true;
            $this->tip = strval($this->order->totals->tip->getAmount() / 100);
        }
    }

    public function boot(PaymentGateway $gateway): void
    {
        $this->gateway = $gateway;
    }

    public function updatedAddTip(): void
    {
        if ($this->addTip === false && !$this->order->isLocked()) {
            $this->reset('tip');
        }
    }

    public function updatedSplitAmountInput(): void
    {
        if (filled($this->splitAmountInput)) {
            $this->itemsToPay = new Collection;
        }
    }

    public function pay(): void
    {
        $this->validate();

        if ($this->addTip && filled($this->tip) && !$this->order->isLocked()) {
            $this->order->addTip(Money::parse(floatval($this->tip)));
            $this->order->save();
        }

        if ($this->amountToPay->isZero()) {
            return;
        }

        $this->gateway->pay(
            $this->order,
            $this->amountToPay,
            new CreditCard(
                number: Str::numbers($this->creditCardNumber),
                cardHolder: $this->cardHolder,
                expirationDate: $this->expirationDate,
                cvv: $this->cvc,
            ),
            $this->splitPayment ? $this->itemsToPay : null
        );

        $this->order->refresh();
        $this->itemsToPay = new Collection;
        unset($this->amountToPay);
        unset($this->splitAmount);
    }

    public function addItemToPay(Item $item): void
    {
        if (!isset($this->order->unpaid_items[$item->id])) {
            return;
        }

        $this->itemsToPay[$item->id] = min(
            $this->order->unpaid_items[$item->id]?->quantity ?? 0,
            ($this->itemsToPay[$item->id] ?? 0) + 1
        );

        $this->reset('splitAmountInput');
    }

    public function removeItemToPay(Item $item): void
    {
        if (!isset($this->itemsToPay[$item->id])) {
            return;
        }

        $this->itemsToPay[$item->id]--;

        if ($this->itemsToPay[$item->id] === 0) {
            unset($this->itemsToPay[$item->id]);
        }

        $this->reset('splitAmountInput');
    }

    #[Computed]
    public function amountToPay(): Money
    {
        if ($this->splitPayment) {
            return Money::min($this->splitAmount, $this->order->remainder);
        }

        return $this->order->remainder->add($this->tipSplit($this->order->remainder));
    }

    #[Computed]
    public function splitAmount(): Money
    {
        if ($this->itemsToPay->isNotEmpty() && $this->isLastPayment()) {
            return $this->order->remainder;
        }

        if ($this->itemsToPay->isNotEmpty()) {
            $amount = $this->subtotalSplit();

            return $amount->add($this->tipSplit($amount));
        }

        if (filled($this->splitAmountInput)) {
            return Money::parse(floatval($this->splitAmountInput));
        }

        return Money::parse(0);
    }

    #[Computed]
    public function orderSubtotal(): Money
    {
        return $this->order->totals->subtotal;
    }

    #[Computed]
    public function orderTip(): Money
    {
        if ($this->order->isLocked()) {
            return $this->order->totals->tip;
        }

        return $this->addTip ? Money::parse(floatval($this->tip)) : Money::parse(0);
    }

    #[Computed]
    public function orderTotal(): Money
    {
        if ($this->order->isLocked()) {
            return $this->order->total;
        }

        return $this->order->total->add($this->orderTip);
    }

    private function isLastPayment(): bool
    {
        return $this->order->unpaid_items
            ->removePaidItems($this->itemsToPay)
            ->isEmpty();
    }

    private function subtotalSplit(): Money
    {
        $items = new Collection($this->itemsToPay)
            ->map(fn(int $quantity, int $itemId) => $this->order->items[$itemId]->unitPrice->multiply($quantity));

        return Money::sum(...$items);
    }

    private function tipSplit(Money $amountToPay): Money
    {
        if ($this->tip === 0 || $amountToPay->isZero()) {
            return Money::parse(0);
        }

        [$tip] = Money::parse(floatval($this->tip))->allocate([
            $amountToPay->ratioOf($this->order->total),
            $this->order->total->subtract($amountToPay)->ratioOf($this->order->total)
        ]);

        return $tip;
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

        <div class="space-y-6">
            <flux:switch wire:model.live="addTip" label="Add tip" :disabled="$order->isLocked()"/>

            @if($addTip)
                <flux:field>
                    <flux:input wire:model.live.debounce.500="tip" mask:dynamic="$money($input)"
                                :readonly="$order->isLocked()"/>
                    <flux:error name="tip"/>
                </flux:field>
            @endif
        </div>

        <div class="space-y-6">
            <div class="flex">
                <flux:heading>Subtotal</flux:heading>
                <flux:spacer/>
                <flux:heading>{{ $this->orderSubtotal }}</flux:heading>
            </div>

            <div class="flex">
                <flux:heading>Tip</flux:heading>
                <flux:spacer/>
                <flux:heading>{{ $this->orderTip }}</flux:heading>
            </div>

            <div class="flex">
                <flux:heading>Total</flux:heading>
                <flux:spacer/>
                <flux:heading>{{ $this->orderTotal }}</flux:heading>
            </div>

            @if($order->isPartiallyPaid())
                <div class="flex">
                    <flux:heading>Paid</flux:heading>
                    <flux:spacer/>
                    <flux:heading>{{ $order->total_paid }}</flux:heading>
                </div>
                <div class="flex">
                    <flux:heading>Remainder</flux:heading>
                    <flux:spacer/>
                    <flux:heading>{{ $order->remainder }}</flux:heading>
                </div>
            @endif
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
                        @foreach($this->order->unpaid_items as $orderItem)
                            <div class="flex justify-between">
                                <flux:heading>x{{ $orderItem->quantity }} {{ $orderItem->name }}</flux:heading>
                                <flux:button.group size="sm">
                                    <flux:button icon="minus" wire:click="removeItemToPay({{ $orderItem->itemId }})"/>
                                    <flux:button disabled>{{ $itemsToPay[$orderItem->itemId] ?? 0 }}</flux:button>
                                    <flux:button icon="plus" wire:click="addItemToPay({{ $orderItem->itemId }})"/>
                                </flux:button.group>
                            </div>
                        @endforeach

                        <flux:separator text="or"/>

                        <flux:field>
                            <flux:input type="number" placeholder="Amount to pay"
                                        wire:model.live.debounce.500ms="splitAmountInput"
                                        mask:dynamic="$money($input)"/>
                            <flux:error name="splitAmountInput"/>
                        </flux:field>
                    </div>
                @endif

                <flux:button type="submit" class="w-full" :disabled="$this->amountToPay->isZero()">
                    Pay {{ $this->amountToPay }}</flux:button>
            </flux:card>
        </form>
    @endif

    @if($order->isPartiallyPaid())
        <flux:card class="space-y-6">
            <flux:heading>Payment Link</flux:heading>
            <flux:text>Share this link with your friends to complete the payment.</flux:text>
            <flux:input :value="route('app.pay', ['order' => $order, 'split' => $splitPayment])" class="mt-4" readonly
                        copyable/>
            <flux:separator text="or"/>
            <flux:text class="text-center">Scan</flux:text>
            <img src="{{ new QRCode()->render(route('app.pay', ['order' => $order, 'split' => $splitPayment])) }}"/>
        </flux:card>
    @endif
</div>
