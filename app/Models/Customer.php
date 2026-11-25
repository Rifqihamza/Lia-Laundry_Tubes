<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasUuids;

    protected $fillable = ['name', 'phone', 'notes'];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
