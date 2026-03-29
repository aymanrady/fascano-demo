<?php

use App\Enums\Role;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title("Restaurants")]
class extends Component {
    use WithPagination;

    public User $partner;
    public string $sortBy = 'created_at';
    public string $sortDirection = 'desc';

    public function mount(User $partner): void
    {
        $this->partner = $partner;
    }

    public function sort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'desc' ? 'asc' : 'desc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'desc';
        }
    }

    /**
     * @return LengthAwarePaginator<int, Restaurant>
     */
    #[Computed]
    public function restaurants(): LengthAwarePaginator
    {
        return $this->partner
            ->restaurants()
            ->when(
                $this->sortBy === 'created_at',
                fn(Builder $query) => $query->orderBy($this->sortBy, $this->sortDirection)
            )
            ->when(
                $this->sortBy === 'tables',
                fn(Builder $query) => $query->withCount('tables')->orderBy('tables_count', $this->sortDirection)
            )
            ->paginate(10);
    }
};
?>

<section class="w-full">
    <flux:heading size="xl" level="1" class="mb-6">{{ __(':name\'s Restaurants', ['name' => $partner->name]) }}</flux:heading>
    <flux:table :paginate="$this->restaurants">
        <flux:table.columns>
            <flux:table.column>Name</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'tables'" :direction="$sortDirection" wire:click="sort('tables')">tables</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'created_at'" :direction="$sortDirection" wire:click="sort('created_at')">Registered</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->restaurants as $restaurant)
                <flux:table.row :key="$restaurant->id">
                    <flux:table.cell>{{ $restaurant->name }}</flux:table.cell>
                    <flux:table.cell variant="strong">{{ $restaurant->tables->count() }}</flux:table.cell>
                    <flux:table.cell>{{ $restaurant->created_at }}</flux:table.cell>

                    <flux:table.cell>
                        <flux:button :href="route('restaurants.show', ['restaurant' => $restaurant->id])" variant="ghost"
                                     size="sm" icon="eye" inset="top bottom"></flux:button>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</section>
