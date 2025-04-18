<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $guarded = []; // opposite of protected fillable

    public function user(){
        return $this->belongsTo(User::class);
    }

    // A Room can have many schedules
    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }

    // A Room can have many OTP requests
    public function otpRequests()
    {
        return $this->hasMany(Otp_request::class);
    }

    // A Room can have many access logs
    public function accessLogs()
    {
        return $this->hasMany(Accesslog::class);
    }

        // A Room can belong to many Courses through the schedule pivot table
    public function courses()
    {
        return $this->belongsToMany(Course::class, 'schedule');
    }

}
