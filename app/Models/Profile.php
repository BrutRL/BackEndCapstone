<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Profile extends Model
{

    use HasFactory;


    protected $guarded = []; //guarded mean you type didt'n fillable

    public function user(){
        return $this->belongsTo(User::class);
    }
}
