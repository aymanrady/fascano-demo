<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Menu\Item;
use App\Models\Restaurant;
use App\Models\Table;
use App\Models\User;
use Illuminate\Database\Seeder;

class RestaurantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Restaurant::factory(1)
            ->recycle(User::role(Role::Partner)->get())
            ->create()
            ->each(function (Restaurant $restaurant) {
                Table::factory()
                    ->for($restaurant)
                    ->count(10)
                    ->sequence(fn ($sequence) => ['number' => $sequence->index + 1])
                    ->create();
            })
            ->each(function (Restaurant $restaurant) {
                Item::factory()
                    ->for($restaurant)
                    ->count(20)
                    ->create();
            });
    }
}
