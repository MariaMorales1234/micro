<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model {
    protected $table = "users";
    public $timestamps = false;
    protected $hidden = ['password']; //oculta columnas de la tabla de la base de datos
}