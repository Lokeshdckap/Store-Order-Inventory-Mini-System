<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'name',
        'email',
    ];

    protected static function booted(): void
    {
        static::creating(function ($customer) {
            if (empty($customer->uuid)) {
                $customer->uuid = (string) \Illuminate\Support\Str::uuid();
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

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class)->latest();
    }
}
