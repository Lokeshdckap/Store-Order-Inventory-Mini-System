<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'name',
        'code',
        'price',
        'tax_percentage',
        'stock',
    ];

    protected $casts = [
        'price'          => 'decimal:2',
        'tax_percentage' => 'decimal:2',
        'stock'          => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function ($product) {
            if (empty($product->uuid)) {
                $product->uuid = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

    /**
     * Use uuid for all URL / route key lookups.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function scopeLowStock(Builder $query, int $threshold): Builder
    {
        return $query->where('stock', '<=', $threshold);
    }
}
