<?php

namespace App\Http\Controllers;
use App\Models\Otp_request;
use App\Models\Room;
use App\Models\Schedule;
use Validator;
use Carbon\Carbon;
use Illuminate\Http\Request;

class Otp_requestController extends Controller
{
    /**
     * Creates an Otp_request to inputs from Request
     * POST:: /api/otp_request
     * @param Request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
{
    $validator = Validator::make($request->all(), [
        'room_id' => 'required|exists:rooms,id',
        'user_id' => 'required|exists:users,id',
        'Access_code' => 'sometimes|string|regex:/^\d{6}$/',
        'generated_at' => ['required', 'date',
            function ($attribute, $value, $fail) {
                if (Carbon::parse($value)->isBefore(Carbon::today())) {
                    $fail('The date must be today or a future date.');
                }
            },
        ],
        'used_at' => 'required|date_format:H:i',
        'end_time' => 'required|date_format:H:i|after:used_at',
        'purpose' => 'required|string|max:255',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'ok' => false,
            'message' => "Validation failed!",
            'errors' => $validator->errors()
        ], 400);  
    }

    $validated = $validator->validated();

    // Special logic for R404
    $r404RoomId = Room::where('name', 'R404')->value('id');
    \Log::info('R404 Room ID:', ['id' => $r404RoomId]);

    if ($validated['room_id'] == $r404RoomId) {
        $existingOtpWithCode = Otp_request::where('room_id', $r404RoomId)
            ->where('user_id', $validated['user_id'])
            ->whereNotNull('Access_code')
            ->whereDate('generated_at', Carbon::parse($validated['generated_at'])->toDateString())
            ->where(function ($query) use ($validated) {
                $query->where('used_at', '<', $validated['end_time'])
                      ->where('end_time', '>', $validated['used_at']);
            })
            ->first();

        if ($existingOtpWithCode) {
            return response()->json([
                'ok' => true,
                'data' => $existingOtpWithCode,
                'message' => 'You already have an access code for R404 at the requested time.',
            ], 200);
        }
    }

    // Conflict check: Only allow if no accepted request exists for the same room, same date, and overlapping time
$otpConflict = Otp_request::where('room_id', $validated['room_id'])
    ->where('access_status', 1) // Only accepted requests
    ->whereDate('generated_at', Carbon::parse($validated['generated_at'])->toDateString())
    ->where(function ($query) use ($validated) {
        $query->where('used_at', '<', $validated['end_time'])
              ->where('end_time', '>', $validated['used_at']);
    })
    ->exists();

if ($otpConflict) {
    return response()->json([
        'ok' => false,
        'message' => 'This room is already requested and accepted for the selected date and time.',
    ], 409);
}
    // Schedule conflict check (same room only)
    $overlappingSchedule = Schedule::where('room_id', $validated['room_id'])
        ->whereDate('assigned_date', '<=', Carbon::parse($validated['generated_at'])->toDateString())
        ->whereDate('end_date', '>=', Carbon::parse($validated['generated_at'])->toDateString())
        ->where(function ($query) use ($validated) {
            $query->where('start_time', '<', $validated['end_time'])
                  ->where('end_time', '>', $validated['used_at']);
        })
        ->exists();

    if ($overlappingSchedule) {
        return response()->json([
            'ok' => false,
            'message' => 'Sorry, this room is already scheduled for the selected date and time.',
        ], 409);
    }

    // Create a new OTP request
    $otpRequest = Otp_request::create([
        'room_id' => $validated['room_id'],
        'user_id' => $validated['user_id'],
        'Access_code' => $validated['Access_code'] ?? (
            $validated['room_id'] == $r404RoomId 
                ? str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT) 
                : null
        ),
        'access_status' => 2, // Pending status
        'generated_at' => $validated['generated_at'],
        'used_at' => $validated['used_at'],
        'end_time' => $validated['end_time'],
        'purpose' => $validated['purpose'],
    ]);

    return response()->json([
        'ok' => true,
        'data' => $otpRequest,
        'message' => 'Your room request has been successfully created. Wait for admin approval.',
    ], 201);
}

    /**
     * Retrieve all otp_requests from Request
     * GET: /api/otp_request
     * @param Request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $OtpRequests = Otp_request::with(['room', 'user.profile'])->get();
        return response()->json([
            "ok" => true,
            "data" => $OtpRequests,
            "message" => "All Rooms has been retrieved successfully."
        ], 200);
    }

    /**
     * Retrieve specific Otp using id
     * GET: /api/otp_request/{otp_request}
     * @param Request
     * @param Otp_request
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Otp_request $OtpRequest)
    {
        return response()->json([
            'ok' => true,
            'message' => 'Room request details retrieved successfully.',
            'data' => $OtpRequest
        ], 200);
    }

    /**
     * Update specific Otp_request using inputs from Request and id from URI
     * PATCH: /api/otp_request/{otp_request}
     * @param Request
     * @param Otp_request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
{
    $validator = Validator::make($request->all(), [
        'access_status' => 'sometimes|in:1,2,3',
        'room_id' => 'sometimes|exists:rooms,id',
        'user_id' => 'sometimes|exists:users,id',
        'Access_code' => 'sometimes|string|regex:/^\d{6}$/',
        'generated_at' => 'sometimes|date',
        'used_at' => 'sometimes|date_format:H:i',
        'end_time' => 'sometimes|date_format:H:i|after:used_at',
        'purpose' => 'sometimes|string|max:255',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'ok' => false,
            'message' => "Validation failed!",
            'errors' => $validator->errors()
        ], 400);
    }

    $otpRequest = Otp_request::find($id);
    if (!$otpRequest) {
        return response()->json([
            'ok' => false,
            'message' => 'OTP request not found!',
        ], 404);
    }

    $otpRequest->fill($request->only([
        'room_id', 'user_id', 'generated_at', 'used_at', 'end_time', 'purpose', 'Access_code'
    ]));

    // Conflict check: Only for accepted status (1)
    if ($request->access_status == 1) {
        $roomId = $otpRequest->room_id;
        $generatedAt = $otpRequest->generated_at;
        $usedAt = $otpRequest->used_at;
        $endTime = $otpRequest->end_time;

        // Check for other accepted requests in the same room, same date, overlapping time, excluding this request
        $existingAcceptedRequest = Otp_request::where('room_id', $roomId)
            ->where('id', '!=', $otpRequest->id)
            ->whereDate('generated_at', Carbon::parse($generatedAt)->toDateString())
            ->where(function ($query) use ($usedAt, $endTime) {
                $query->where('used_at', '<', $endTime)
                      ->where('end_time', '>', $usedAt);
            })
            ->where('access_status', 1)
            ->first();

        if ($existingAcceptedRequest) {
            return response()->json([
                'ok' => false,
                'message' => 'This time slot is already taken by an accepted request.',
            ], 409);
        }

        $otpRequest->access_status = 1;

        $room = $otpRequest->room;
        if ($room && $room->name === 'R404') {
            $otpRequest->Access_code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        }

        // Set other pending requests for this slot to rejected (3)
        Otp_request::where('room_id', $roomId)
            ->where('id', '!=', $otpRequest->id)
            ->whereDate('generated_at', Carbon::parse($generatedAt)->toDateString())
            ->where('access_status', 2)
            ->where(function ($query) use ($usedAt, $endTime) {
                $query->where('used_at', '<', $endTime)
                      ->where('end_time', '>', $usedAt);
            })
            ->update(['access_status' => 3]);
    } elseif ($request->access_status == 3) {
        $otpRequest->access_status = 3;
    }

    if (
        $otpRequest->access_status === 1 && 
        $request->has('Access_code') &&
        preg_match('/^\d{6}$/', $request->Access_code)
    ) {
        $otpRequest->Access_code = $request->Access_code;
    }

    $otpRequest->save();

    return response()->json([
        'ok' => true,
        'data' => $otpRequest,
        'message' => 'Room request updated successfully.',
    ], 200);
}

    /**
     * Delete specific Otp_request using id from URI
     * DELETE: /api/otp_request/{otp_request}
     * @param Request
     * @param Otp_request
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, Otp_request $OtpRequest)
    {
        $OtpRequest->delete();
        return response()->json([
            "ok" => true,
            "message" => "Request Room has been Deleted!."
        ], 201);
    }
}