<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    protected $fillable = [
        'id', 'user_id', 'status', 'total_price', 'subtotal', 'delivered_date', 'canceled_date'
    ];

    public function user(){
        return $this->belongsTo(User::class);
    }
    public function orderItems(){
        return $this->hasMany(OrderItem::class);
    }
    public function transaction(){
        return $this->hasOne(Transaction::class);
    }
}
