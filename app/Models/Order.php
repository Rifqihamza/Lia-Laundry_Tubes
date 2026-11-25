<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasUuids;

    protected $fillable = [
        'invoice_code',
        'customer_id',
        'service_id',
        'weight_kg',
        'total_price',
        'payment_method',
        'payment_status',
        'laundry_status',
        'confirmed_by',
        'processed_by',
        'completed_at',
        'picked_up_at',
    ];

    protected $casts = [
        'weight_kg' => 'decimal:2',
        'total_price' => 'decimal:2',
        'completed_at' => 'datetime',
        'picked_up_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function service()
    {
        return $this->belongsTo(LaundryService::class, 'service_id');
    }

    public function kasir()
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function karyawan()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function waLogs()
    {
        return $this->hasMany(WaLogs::class);
    }
}
