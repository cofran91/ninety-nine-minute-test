<?php

namespace App\Models;

use App\Models\Address;
use App\Models\User;
use App\Models\Status;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

     protected $fillable = [
        'id',
        'status_id',
        'user_id',
        'origin_address_id',
        'arrival_address_id',
        'product_amount',
        'product_weight',
        'value',
        'devolution',
        'created_at',
        'updated_at'
    ];

    public function originAddress()
    {
        return $this->belongsTo( Address::class, 'origin_address_id', 'id' );
    }

    public function arrivalAddress()
    {
        return $this->belongsTo( Address::class, 'arrival_address_id', 'id' );
    }

    public function status()
    {
        return $this->belongsTo( Status::class );
    }

    public function user()
    {
        return $this->belongsTo( User::class );
    }
}
