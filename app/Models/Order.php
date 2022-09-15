<?php

namespace App\Models;

use App\Models\Address;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

     protected $fillable = [
        'id',
        'status_id',
        'client_id',
        'product_weight',
        'product_weight',
        'arrival_address_id',
        'product_amount',
        'value',
        'created_at',
        'updated_at',
    ];

    protected $hidden = [
        'password',
    ];

    public function originAddress()
    {
        return $this->belongsTo( Address::class, 'origin_address_id', 'id' );
    }

    public function arrivalAddress()
    {
        return $this->belongsTo( Address::class, 'arrival_address_id', 'id' );
    }
}
