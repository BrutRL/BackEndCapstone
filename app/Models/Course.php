<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Course extends Model


{

    use SoftDeletes;
    protected $guarded = [];
    protected $dates = ['deleted_at']; // Enable the soft delete feature


    public function schedule(){
        return $this->hasMany(Schedule::class);
    }

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function rooms(){
        return $this->belongsToMany(Room::class, 'schedule');
    }
}
