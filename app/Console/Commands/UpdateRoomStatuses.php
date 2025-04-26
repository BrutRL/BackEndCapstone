<?php
namespace App\Console\Commands;

use App\Models\Room;
use App\Models\Otp_request;
use Carbon\Carbon;
use Illuminate\Console\Command;

class UpdateRoomStatuses extends Command
{
    protected $signature = 'edit:room-status';
    protected $description = 'Update room status based on OTP and schedule end times';

    public function handle()
    {
        $now = Carbon::now();
        $expiredOtpRequests = Otp_request::where('end_time', '<', $now->format('H:i'))
            ->whereDate('generated_at', $now->toDateString())
            ->where('access_status', 1) // Active access
            ->get();

        foreach ($expiredOtpRequests as $request) {
            $room = $request->room;

            if ($room && $room->status !== 'Available') {
                $room->status = 'Available';
                $room->save();
            }
            $request->access_status = 4;
            $request->save();
        }
        $rooms = Room::with('schedules')->get();

        foreach ($rooms as $room) {
            $activeSchedule = $room->schedules()
                ->whereDate('end_date', '>=', now()->toDateString())
                ->whereTime('end_time', '>=', now()->format('H:i'))
                ->first();

            $isAvailable = !$activeSchedule;

            if ($isAvailable && $room->status !== 'Available') {
                $room->status = 'Available';
                $room->save();
            } elseif (!$isAvailable && $room->status !== 'Occupied') {
                $room->status = 'Occupied';
                $room->save();
            }
        }

        $this->info('Room statuses (OTP + Schedule) updated.');
    }
}