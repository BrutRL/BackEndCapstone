<?php

namespace App\Http\Controllers;

use App\Models\LoginHistory;
use App\Models\Schedule;
use App\Models\Room;
use Carbon\Carbon;
use App\Models\Otp_request;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ScheduleController extends Controller
{
    /**
     * Create a Schedule.
     * POST: /api/schedules
     */
    public function store(Request $request)
    {
        $request->merge([
            'days_in_week' => is_string($request->days_in_week) ? json_decode($request->days_in_week, true) : $request->days_in_week
        ]);
    
        $validator = Validator::make($request->all(), [
            "room_id" => "required|exists:rooms,id",
            "user_id" => "required|exists:users,id",
            "course_id" => "required|exists:courses,id",
            "assigned_date" => "required|date",
            "end_date" => "required|date|after_or_equal:assigned_date",
            "start_time" => "required|date_format:H:i",
            "end_time" => "required|date_format:H:i|after:start_time",
            "days_in_week" => "required|array|min:1",
            "days_in_week.*" => "integer|min:1|max:7",
            "year" => "required|string|min:1|max:32",
        ]);
    
        if ($validator->fails()) {
            return $this->errorResponse("Validation failed", $validator->errors(), 400);
        }
    
        $validated = $validator->validated();
    
        $startDate = Carbon::parse($validated['assigned_date']);
        $endDate = Carbon::parse($validated['end_date']);
        $daysInWeek = $validated['days_in_week'];
    
        $dateContainsValidDay = false;
        $dateRange = clone $startDate;
    
        while ($dateRange->lte($endDate)) {
            if (in_array($dateRange->dayOfWeekIso, $daysInWeek)) {
                $dateContainsValidDay = true;
                break;
            }
            $dateRange->addDay();
        }
    
        if (!$dateContainsValidDay) {
            return response()->json([
                'ok' => false,
                'message' => 'The date range must include at least one of the selected days of the week.',
            ], 422);
        }
    
        // Check for overlapping schedules
        $overlappingschedule = Schedule::where('room_id', $validated['room_id'])
            ->where(function ($query) use ($validated) {
                $query->where(function ($q) use ($validated) {
                    $q->where('start_time', '<', $validated['end_time'])
                        ->where('end_time', '>', $validated['start_time']);
                });
            })
            ->where(function ($query) use ($validated) {
                $query->whereDate('assigned_date', $validated['assigned_date'])
                    ->orWhereJsonContains('days_in_week', Carbon::parse($validated['assigned_date'])->dayOfWeekIso);
            })
            ->exists();
    
        if ($overlappingschedule) {
            return response()->json([
                'ok' => false,
                'message' => 'This time slot is already taken for the selected room on the same date or day.',
            ], 409);
        }
    
        // Create the schedule
        $schedule = Schedule::create([
            "room_id" => $validated["room_id"],
            "user_id" => $validated["user_id"],
            "course_id" => $validated["course_id"],
            "assigned_date" => $validated["assigned_date"],
            "end_date" => $validated["end_date"],
            "start_time" => Carbon::createFromFormat('H:i', $validated["start_time"])->format('H:i'),
            "end_time" => Carbon::createFromFormat('H:i', $validated["end_time"])->format('H:i'),
            "days_in_week" => json_encode($validated["days_in_week"]),
            "year" => $validated["year"],
            "status" => 1,
        ]);
    
        // Prepare access code if applicable
        $accessCode = null;
        $room = Room::find($validated["room_id"]);
    
        if ($room && $room->name === 'R404') {
            $accessCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT); // always 6-digit string
    
            Otp_request::create([
                'room_id' => $validated["room_id"],
                'user_id' => $validated["user_id"],
                'Access_code' => $accessCode,
                'access_status' => 1, // auto-accepted
                'generated_at' => now(),
                'used_at' => $validated["start_time"],
                'end_time' => $validated["end_time"],
                'purpose' => 'Automatically generated for schedule creation.',
            ]);
        }
    
        LoginHistory::create([
            'user_id' => auth()->id(),
            'username' => auth()->user()->username,
            'role' => auth()->user()->role_id,
            'event' => 'schedule',
            'action' => 'create',
        ]);
    
        return $this->successResponse([
            'schedule' => $schedule,
            'Access_code' => $accessCode ?? null, // will return null if not R404
        ], "Schedule created successfully!", 201);
    }
    
    /**
     * Retrieve all schedules.
     * GET: /api/schedules
     */
    public function index()
    {   
        $schedules = Schedule::with(['user.profile', 'course', 'room'])->get(); 
        return $this->successResponse($schedules, "Schedule retrieved successfully!");
    }

    /**
     * Retrieve a specific schedule.
     * GET: /api/schedules/{schedule}
     */
    public function show(Schedule $schedule)
    {
        $schedule->load(['room', 'user', 'course']);
        return $this->successResponse($schedule, "Schedule retrieved successfully!");
    }

    /**
     * Update a specific schedule.
     * PATCH: /api/schedules/{schedule}
     */
    public function update(Request $request, Schedule $schedule)
    {
        $request->merge([
            'days_in_week' => is_string($request->days_in_week) ? json_decode($request->days_in_week, true) : $request->days_in_week
        ]);
    
        $validator = Validator::make($request->all(), [
            "assigned_date" => "sometimes|date",
            "end_date" => "sometimes|date|after_or_equal:assigned_date",
            "start_time" => "sometimes|date_format:H:i",
            "end_time" => "sometimes|date_format:H:i|after:start_time",
            "days_in_week" => "sometimes|array|min:1",
            "days_in_week.*" => "integer|min:1|max:7",
            "year" => "sometimes|string|min:1|max:32",
            "status" => "sometimes|integer|in:1,2,3", // 1 = Accepted, 2 = Pending, 3 = Cancelled
            "Access_code" => "sometimes|string|regex:/^\d{6}$/", // Validate access_code
        ]);
    
        if ($validator->fails()) {
            return $this->errorResponse("Validation failed", $validator->errors(), 400);
        }
    
        $validated = $validator->validated();
    
        // Update the schedule
        $schedule->update($validated);
    
        // Update the access_code if provided
        if (isset($validated['Access_code'])) {
            Otp_request::where('room_id', $schedule->room_id)
                ->where('user_id', $schedule->user_id)
                ->orderBy('created_at', 'desc')
                ->first()
                ->update(['Access_code' => $validated['Access_code']]);
        }
    
        // Log the update action
        LoginHistory::create([
            'user_id' => auth()->id(),
            'username' => auth()->user()->username,
            'role' => auth()->user()->role_id,
            'event' => 'schedule',
            'action' => 'update',
        ]);
    
        return $this->successResponse($schedule, "Schedule updated successfully!");
    }
    

    /**
     * Access a room with or without OTP.
     * POST: /api/schedules/{schedule}/access
     */
    public function accessRoom(Request $request, Schedule $schedule)
    {
        $validator = Validator::make($request->all(), [
            'otp_code' => 'sometimes|required|digits:6',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse("Validation failed", $validator->errors(), 400);
        }

        $room = $schedule->room;

        if ($request->has('otp_code')) {
            $otpRequest = Otp_request::where('room_id', $room->id)
                ->where('otp_code', $request->otp_code)
                ->first();

            if (!$otpRequest) {
                return $this->errorResponse("Invalid OTP.", null, 400);
            }

            // Grant access with OTP
            $room->availability = 2; // Occupied
            $room->save();

            return $this->successResponse(null, "Room access granted with OTP.");
        }

        // Grant access without OTP
        if ($room->availability == 1) { // Available
            $room->availability = 2; // Occupied
            $room->save();

            return $this->successResponse(null, "Room access granted without OTP.");
        }

        return $this->errorResponse("Room is not available.", null, 400);
    }

    /**
     * Success response helper.
     */
    private function successResponse($data, $message, $status = 200)
    {
        return response()->json([
            'ok' => true,
            'message' => $message,
            'data' => $data
        ], $status);
    }

    /**
     * Error response helper.
     */
    private function errorResponse($message, $errors = null, $status = 400)
    {
        return response()->json([
            'ok' => false,
            'message' => $message,
            'errors' => $errors
        ], $status);
    }

    /**
     * Soft delete a schedule
     * @param $scheduleId
     * @return \Illuminate\Http\JsonResponse
     */
    public function softDeleteSchedule($scheduleId)
    {
        $user = Schedule::findOrFail($scheduleId);
        $user->delete();

        return response()->json([
            'ok' => true,
            'id' => $scheduleId,
            'message' => 'Schedule soft deleted successfully'
        ]);
    }

    /**
     * Restore a soft deleted schedule
     * @param $scheduleId
     * @return \Illuminate\Http\JsonResponse
     */
    public function restoreSchedule($scheduleId)
    {
        $schedule = Schedule::withTrashed()->findOrFail($scheduleId);
        $schedule->restore();

        return response()->json([
            'ok' => true,
            'id' => $scheduleId,
            'message' => 'Schedule restored successfully'
        ]);
    }

    /**
     * Force delete a schedule
     * @param $scheduleId
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($scheduleId)
    {
        $schedule = Schedule::withTrashed()->findOrFail($scheduleId);
        $schedule->forceDelete();

        LoginHistory::create([
            'user_id' => auth()->id(), // The currently authenticated user
            'username' => auth()->user()->username,
            'role' => auth()->user()->role_id, // Log the role of the authenticated user
            'event' => 'schedule',
            'action' => 'delete', // Action type
           ]);

        return response()->json(['message' => 'Schedule permanently deleted']);
    }

    /**
     * Retrieve all soft deleted schedules.
     * GET: /api/schedules/archived
     */
    public function archivedSchedules()
    {
        $schedules = Schedule::onlyTrashed()->get();
        return response()->json([
            'message' => 'Soft-deleted schedules retrieved successfully',
            'data' => $schedules
        ], 200);
    }
}
