<?php

namespace Database\Factories\Menu;

use App\Enums\Menu\Section;
use App\Models\Menu\Item;
use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Factories\Factory;

use function money;

/**
 * @extends Factory<Item>
 */
class ItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $restaurantFaker = new \FakerRestaurant\Provider\en_US\Restaurant(fake());
        $section = fake()->randomElement(Section::cases());

        return [
            'name' => match ($section) {
                Section::Appetizers, Section::Entrees => $restaurantFaker->foodName(),
                Section::Desserts => $restaurantFaker->fruitName(),
                Section::Beverages => $restaurantFaker->beverageName(),
            },
            'price' => money(fake()->numberBetween(1000, 30000)),
            'description' => fake()->sentence(),
            'section' => $section,
            'restaurant_id' => Restaurant::factory(),
        ];
    }
}
