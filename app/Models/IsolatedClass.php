<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IsolatedClass extends Model
{
    protected $fillable =[
        'user_id',
        'name',
        'observation',
        'value',
        'date'
    ];

    public function user(){
        return $this->belongsTo(User::class);
    } 
}
