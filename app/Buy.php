<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\User;
class Buy extends Model
{
    //
    protected $table ='buy';
    protected $fillable = [
		'id',
        'user_id',
        'sendData',
        'billingData',
        'transaction',
        'card',
        'products',
        'subtotal',
        'send',
        'iva',
        'total',
        'description',
        'status',
        'method',
        'voucher',
        'productsArray'
    ];
    protected $private = [
        'transaction',
        'card',
        'sendData',
        'billingData',
    ];
    public function user_app(){
        return $this->hasMany(User::class, 'id', 'user_id');
    }
}
