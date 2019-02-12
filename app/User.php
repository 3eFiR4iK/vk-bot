<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
   public $timestamps = false;
    protected  $table = 'users';
    
    
    public function groups(){
        return $this->belongsTo(\App\Groups::class,'group_id','id');
    }
    
    

}
