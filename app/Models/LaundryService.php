<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class LaundryService extends Model
{
    use HasUuids;

    protected $fillable = ['service_name', 'price_per_kg', 'estimated_days', 'is_active'];

    protected $casts = [
        'price_per_kg' => 'decimal:2',
        'is_active' => 'boolean',
    ];
}
