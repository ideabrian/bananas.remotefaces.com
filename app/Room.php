<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{        
    //protected $hidden = [];

    protected $fillable = ['name', 'privacy', 'slug'];
}