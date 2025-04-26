<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Otp_request extends Model
{


    // Specify the custom created timestamp
    const CREATED_AT = 'generated_at';

    // Disable the updated timestamp
    const UPDATED_AT = null;

    protected $fillable = [
        'room_id',
        'user_id',
        'Access_code',  // ito ang nabago dating otp_code
        'access_status',
        'generated_at',
        'used_at',
        'end_time',
        'purpose',
    ];

    //protected $hidden = ['otp_code'];
    //protected $guarded = [];

    // An OTPRequest belongs to a Room
    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    // An OTPRequest belongs to a User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function course () {
        return $this->belongsTo(Course::class);
    }
}
