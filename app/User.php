<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    public $timestamps = false;
    protected  $table = 'users';
    
    
    public function groups(){
        return $this->belongsTo(\App\Groups::class,'group_id','id');
    }
}
