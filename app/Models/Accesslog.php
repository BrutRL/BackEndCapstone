<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Accesslog extends Model
{
    use SoftDeletes;
    const CREATED_AT ='accessed_at';

    // Disable the updated timestamp
    const UPDATED_AT = null;

    protected $fillable = [
        'room_id',
        'user_id',
        'otp_request_id',
        'accessed_at',
        'used_at',
        'end_time',
        'access_status',
    ];
    protected $dates = ['deleted_at'];


    //protected $guarded = [];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    // An AccessLog belongs to a User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // An AccessLog may belong to an OTPRequest (nullable)
    public function otpRequest()
    {
        return $this->belongsTo(Otp_request::class);
    }
}
