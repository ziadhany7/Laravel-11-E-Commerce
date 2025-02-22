<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;
    protected $fillable = [
        'id',
        'order_id',
        'user_id', // Allow mass assignment for 'user_id'
        'status',
        'payment_method',
        'amount',
        'transaction_date',
        // Add any other necessary fields
    ];
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
