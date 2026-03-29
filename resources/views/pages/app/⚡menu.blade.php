<?php

use App\Enums\Menu\Section;
use App\Models\Menu\Item;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\Table;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Session;
use Livewire\Component;

new #[Layout('layouts::frontend')]
class extends Component {
    public Order $order;

    /**
     * @return Collection<value-of<Section>, Item>
     */
    #[Computed]
    public function items(): Collection
    {
        return $this->table->restaurant->menu->groupBy('section');
    }

    #[Computed]
    public function restaurant(): Restaurant
    {
        return $this->table->restaurant;
    }

    #[Computed]
    public function table(): Table
    {
        return $this->order->table;
    }

    public function add(Item $item): void
    {
        $this->order->add($item);
        $this->order->save();
    }

    public function remove(Item $item): void
    {
        $this->order->remove($item);
        $this->order->save();
    }
};
?>

<div class="space-y-12 max-w-md mx-auto">
    <flux:heading size="xl">{{ $this->restaurant->name }}</flux:heading>

    @if ($order->isLocked() && $order->needsPayment())
        <flux:card wire:key="order-pending-payment">
            <flux:button :href="route('app.pay', ['order' => $order->id])" class="w-full mt-6">
                Complete your order
            </flux:button>
        </flux:card>
    @else
        @foreach(Section::cases() as $section)
            @continue(!$this->items->has($section->value))

            <div class="space-y-4" wire:key="{{ $section->value }}">
                <flux:heading size="lg">{{ $section->label() }}</flux:heading>
                <div class="flex flex-col gap-2">
                    @foreach($this->items->get($section->value) as $item)
                        <flux:card size="sm" class="flex justify-between items-start gap-4" :wire:key="$item->id">
                            <div class="flex flex-col gap-2">
                                <flux:heading size="md">{{ $item->name }}</flux:heading>
                                <flux:text>{{ $item->description }}</flux:text>
                                <flux:heading>{{ $item->price }}</flux:heading>
                            </div>

                            @if(($this->order->items[$item->id]->quantity ?? 0) === 0)
                                <flux:button icon="plus" size="sm" wire:click="add({{ $item->id }})"/>
                            @else
                                <flux:button.group size="sm">
                                    <flux:button icon="minus" wire:click="remove({{ $item->id }})"/>
                                    <flux:button disabled>{{ $this->order->items[$item->id]->quantity }}</flux:button>
                                    <flux:button icon="plus" wire:click="add({{ $item->id }})"/>
                                </flux:button.group>
                            @endif
                        </flux:card>
                    @endforeach
                </div>
            </div>
        @endforeach

        @if($order->needsPayment())
            <flux:card wire:key="pay-order">
                <dl class="space-y-6 text-sm">
                    <div class="flex justify-between">
                        <dt class="font-medium">Total</dt>
                        <dd>{{ $order->total }}</dd>
                    </div>
                </dl>
                <flux:button :href="route('app.pay', ['order' => $order->id])" class="w-full mt-6">
                    Place Order
                </flux:button>
            </flux:card>
        @endisset

    @endif
</div>

