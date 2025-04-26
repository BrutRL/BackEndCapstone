<?php

namespace App\Http\Controllers;
use App\Models\Otp_request;
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
            'generated_at' => 'required|date',
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
        $existingAcceptedRequest = Otp_request::where('room_id', $validated['room_id'])
            ->whereDate('generated_at', Carbon::parse($validated['generated_at'])->toDateString())
            ->where('access_status', 1)
            ->where(function ($query) use ($validated) {
                $query->where('used_at', '<', $validated['end_time'])
                    ->where('end_time', '>', $validated['used_at']);
            })
            ->exists();

        if ($existingAcceptedRequest) {
            return response()->json([
                'ok' => false,
                'message' => 'This room already has an accepted request for this date and time.',
            ], 409);
        }
        $otpRequest = Otp_request::create([
            'room_id' => $validated['room_id'],
            'user_id' => $validated['user_id'],
            'Access_code' => $validated['Access_code'] ?? null,
            'access_status' => 2,
            'generated_at' => $validated['generated_at'],
            'used_at' => $validated['used_at'],
            'end_time' => $validated['end_time'],
            'purpose' => $validated['purpose'],
        ]);
        return response()->json([
            'ok' => true,
            'data' => $otpRequest,
            'message' => 'Your request Room is Successfully Created, Wait for Admin Approval.',
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
        $OtpRequests = Otp_request::with(['room', 'user'])->get();
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
            'access_status' => 'sometimes|in:1,2',
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
            'room_id', 'user_id', 'generated_at', 'used_at', 'end_time', 'purpose'
        ]));
    
        if ($request->access_status == 1) {
            $existingAcceptedRequest = Otp_request::where('room_id', $otpRequest->room_id)
                ->where(function ($query) use ($otpRequest) {
                    $query->where('used_at', '<', $otpRequest->end_time)
                          ->where('end_time', '>', $otpRequest->used_at);
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
            if ($otpRequest->room->name === 'R404') {
                if ($request->has('Access_code')) {
                    $otpRequest->Access_code = $request->Access_code;
                } else {
                    $otpRequest->Access_code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                }
            }
    
            Otp_request::where('room_id', $otpRequest->room_id)
                ->whereDate('generated_at', Carbon::parse($otpRequest->generated_at)->toDateString())
                ->where('access_status', 2)
                ->where(function ($query) use ($otpRequest) {
                    $query->where('used_at', '<', $otpRequest->end_time)
                          ->where('end_time', '>', $otpRequest->used_at);
                })
                ->update(['access_status' => 3]);
        }
        if ($request->has('Access_code') && preg_match('/^\d{6}$/', $request->Access_code)) {
            $otpRequest->Access_code = $request->Access_code;
        }
    
        $otpRequest->save();
    
        return response()->json([
            'ok' => true,
            'data' => $otpRequest,
            'message' => 'OTP request updated successfully.',
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