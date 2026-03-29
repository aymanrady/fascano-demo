<?php

use App\Enums\Role;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title("Partners")]
class extends Component {
    use WithPagination;

    public string $sortBy = 'created_at';
    public string $sortDirection = 'desc';

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
     * @return LengthAwarePaginator<int, User>
     */
    #[Computed]
    public function partners(): LengthAwarePaginator
    {
        return User::role(Role::Partner)
            ->when(
                $this->sortBy === 'created_at',
                fn(Builder $query) => $query->orderBy($this->sortBy, $this->sortDirection)
            )
            ->when(
                $this->sortBy === 'restaurants',
                fn(Builder $query) => $query->withCount('restaurants')->orderBy('restaurants_count', $this->sortDirection)
            )
            ->paginate(10);
    }
};
?>

<section class="w-full">
    <flux:heading size="xl" level="1" class="mb-6">{{ __('Partners') }}</flux:heading>
    <flux:table :paginate="$this->partners">
        <flux:table.columns>
            <flux:table.column>Name</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'restaurants'" :direction="$sortDirection" wire:click="sort('restaurants')">Restaurants</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'created_at'" :direction="$sortDirection" wire:click="sort('created_at')">Registered</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->partners as $partner)
                <flux:table.row :key="$partner->id">
                    <flux:table.cell>{{ $partner->name }}</flux:table.cell>
                    <flux:table.cell variant="strong">{{ $partner->restaurants->count() }}</flux:table.cell>
                    <flux:table.cell>{{ $partner->created_at }}</flux:table.cell>

                    <flux:table.cell>
                        <flux:button :href="route('partners.show', ['partner' => $partner->id])" variant="ghost" size="sm" icon="eye" inset="top bottom"></flux:button>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</section>
