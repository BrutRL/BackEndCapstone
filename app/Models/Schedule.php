<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Room;

class Schedule extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'room_id',
        'user_id',
        'course_id',
        'assigned_date',
        'end_date',
        'start_time',
        'end_time',
        'days_in_week',
        'year',
        'status'
    ];

    protected $casts = [
        'days_in_week' => 'array', // Laravel automatically handles JSON casting
    ];

    // Relationships
    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    // Automatically update room availability when a schedule ends
    protected static function boot()
    {
        parent::boot();

        static::updated(function ($schedule) {
            // Check if the schedule has ended
            if ($schedule->end_time <= now()) {
                self::updateRoomStatus($schedule->room_id);
            }
        });

        static::deleted(function ($schedule) {
            // When a schedule is deleted, update the room status
            self::updateRoomStatus($schedule->room_id);
        });
    }

    // Function to update room availability
    private static function updateRoomStatus($roomId)
    {
        $hasActiveSchedule = self::where('room_id', $roomId)
            ->where('end_time', '>', now()) // Check if there are ongoing schedules
            ->exists();

        Room::where('id', $roomId)->update(['status' => $hasActiveSchedule ? 2 : 1]);
    }
}
