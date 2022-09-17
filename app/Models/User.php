<?php

namespace App\Models;

use App\Models\Order;
use App\Models\Rol;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'id',
        'rol_id',
        'name',
        'email',
        'password',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    protected $hidden = [
        'password',
    ];

    /**
    * Get the identifier that will be stored in the subject claim of the JWT.
    *
    * @return mixed
    */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
    * Return a key value array, containing any custom claims to be added to the JWT.
    *
    * @return array
    */
    public function getJWTCustomClaims()
    {
        return [];
    }

    public function setPasswordAttribute($password) {
        $this->attributes['password'] = app('hash')->make($password);
    }

    public function rol()
    {
        return $this->belongsTo( Rol::class );
    }

    public function orders()
    {
        return $this->hasMany( Order::class );
    }
}
