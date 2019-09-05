<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


class Ciudad extends Model
{
    protected $table = 'ciudad';
    protected $primaryKey = 'ciudad'; 
    public $incrementing = false;    

    public static $rules = [
            'ciudad' => 'unique:ciudad',
        ];
    public static $messages = [
            'ciudad.unique' => 'Ciudad ya ingresada.'
        ];
}
