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

new #[Title("Restaurant")]
class extends Component {
    use WithPagination;

    public Restaurant $restaurant;
    public Table $selectedTable;

    /**
     * @return LengthAwarePaginator<int, Table>
     */
    #[Computed]
    public function tables(): LengthAwarePaginator
    {
        return $this->restaurant->tables()->paginate(10);
    }

    public function showQrCode(int $tableId): void
    {
        $this->selectedTable = $this->restaurant->tables()->findSole($tableId);
        $this->modal('table-qr-code')->show();
    }
};
?>

<section class="w-full">
    <flux:heading size="xl" level="1"
                  class="mb-6">{{ __(':restaurant\'s Tables', ['restaurant' => $this->restaurant->name]) }}</flux:heading>
    <flux:table :paginate="$this->tables">
        <flux:table.columns>
            <flux:table.column>Number</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->tables as $table)
                <flux:table.row :key="$table->id">
                    <flux:table.cell>{{ $table->number }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" :color="$table->status->color()"
                                    inset="top bottom">{{ $table->status }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:button variant="ghost" size="sm" icon="qr-code" inset="top bottom"
                                     wire:click="showQrCode({{ $table->id }})"></flux:button>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
    <flux:modal name="table-qr-code">
        @isset($selectedTable)
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg" level="2" class="mb-2">Table {{ $selectedTable->number }}</flux:heading>
                    <flux:text class="mt-2">Scan to start your order</flux:text>
                </div>

                <img src="{{ new QRCode()->render(route('app.start', ['table' => $selectedTable->id])) }}"/>
            </div>
        @endisset
    </flux:modal>
</section>
