<?php

namespace App\Models;

use App\Enums\TableStatus;
use Database\Factories\TableFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['number'])]
class Table extends Model
{
    /** @use HasFactory<TableFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'number' => 'integer',
        ];
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    protected function status(): Attribute
    {
        return Attribute::get(
            fn () => match (true) {
                $this->orders()->pending()->exists() => TableStatus::Occupied,
                $this->orders()->processing()->exists() => TableStatus::Waiting,
                default => TableStatus::Open,
            }
        );
    }
}
