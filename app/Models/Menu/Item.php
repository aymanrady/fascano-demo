<?php

namespace App\Models\Menu;

use App\Enums\Menu\Section;
use App\Models\Restaurant;
use Cknow\Money\Casts\MoneyIntegerCast;
use Database\Factories\Menu\ItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Item extends Model
{
    /** @use HasFactory<ItemFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'section' => Section::class,
            'price' => MoneyIntegerCast::class,
        ];
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }
}
