<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class WaLogs extends Model
{
    use HasUuids;

    public $timestamps = false; // Karena tabel wa_logs hanya memakai kolom sent_at

    protected $fillable = [
        'order_id',
        'message_type',
        'recipient_phone',
        'status',
        'error_message',
        'sent_at',
    ];
}
