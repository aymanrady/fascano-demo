<?php

use App\Enums\Role;
use App\Models\Restaurant;
use App\Models\Table;
use App\Models\User;
use chillerlan\QRCode\QRCode;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title("Orders")]
class extends Component {
    use WithPagination;

    public Restaurant $restaurant;

    /**
     * @return LengthAwarePaginator<int, Table>
     */
    #[Computed]
    public function orders(): LengthAwarePaginator
    {
        return $this->restaurant->orders()->latest()->paginate(10);
    }
};
?>

<section class="w-full">
    <flux:heading size="xl" level="1"
                  class="mb-6">{{ __(':restaurant\'s Orders', ['restaurant' => $this->restaurant->name]) }}</flux:heading>
    <flux:table :paginate="$this->orders">
        <flux:table.columns>
            <flux:table.column>ID</flux:table.column>
            <flux:table.column>Table</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column>Payment Status</flux:table.column>
            <flux:table.column>Total</flux:table.column>
            <flux:table.column>Total Paid</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->orders as $order)
                <flux:table.row :key="$order->id">
                    <flux:table.cell>{{ $order->id }}</flux:table.cell>
                    <flux:table.cell>{{ $order->table->number }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" :color="$order->status->color()"
                                    inset="top bottom">{{ $order->status->label() }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        @if($order->isPaid())
                            <flux:badge size="sm" color="green" inset="top bottom">Paid</flux:badge>
                        @elseif($order->isPartiallyPaid())
                            <flux:badge size="sm" color="yellow" inset="top bottom">Partially Paid</flux:badge>
                        @else
                            <flux:badge size="sm" color="red" inset="top bottom">Pending</flux:badge>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>{{ $order->total }}</flux:table.cell>
                    <flux:table.cell>{{ $order->total_paid }}</flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</section>
