<?php

namespace App\Models;

use App\Models\Rol;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Model
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

    public function rol()
    {
        return $this->belongsTo( Rol::class );
    }
}
