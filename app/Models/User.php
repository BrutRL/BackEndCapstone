<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, SoftDeletes, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'email',
        'role_id',   // 1 = admin|| 2 = user|| default 2
        'password',
    ];
    protected $dates = ['deleted_at']; // Enable the soft delete feature
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function profile(){
        return $this->hasOne(Profile::class); //si user ang ang nag mamay-ari kay profile
    }

    // app/Models/User.php

    public function room(){
        return $this->hasMany(Room::class);
    }

     // A User can have many schedules
    public function schedules(){
        return $this->hasMany(Schedule::class);
    }

     // A User can have many OTP requests
    public function otpRequests(){
        return $this->hasMany(Otp_request::class);
    }

     // A User can have many access logs
    public function accessLogs(){
        return $this->hasMany(Accesslog::class);
    }

    public function courses(){
        return $this->hasMany(Course::class);
    }

}
