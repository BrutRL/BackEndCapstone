<?php

namespace App\Http\Controllers;

use App\Models\Otp_request;
use Validator;
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
        //$user = $request->user(); // Authenticated user

        $validator = Validator::make($request->all(), [
            'room_id' => 'required|exists:rooms,id',
            "user_id" => "required|exists:users,id",
            'Access_code' => 'sometimes|string|regex:/^\d{6}$/', // Ensure Access_code is exactly 6 digits
            'otp_status' => 'required|in:1,2,3',
            "generated_at" => "required|date",
            'used_at' => "required|date_format:H:i",
            "end_time" => "required|date_format:H:i|after:used_at",
            "purpose" => "required|string|min:4|max:255",
        ]);

        if ($validator->fails()) {
            return response()->json([
                'ok' => false,
                'message' => "Validation failed!",
                'error' => $validator->errors()
            ], 400);
        }

        $validated = $validator->validated();

        $Otp_request = Otp_request::create([
           "room_id" => $validated["room_id"],
           "Access_code" => $validated["Access_code"] ?? null,
           "otp_status" => $validated["otp_status"] ?? 3,
           "generated_at" => $validated["generated_at"],
           "user_id" => $validated["user_id"],
           "used_at" => $validated["used_at"],
           "end_time" => $validated["end_time"],
           "purpose" => $validated["purpose"],
        ]);
        return response()->json([
            "ok" => true,
            "data" => $Otp_request,
            "message" => "Opt Request has been created and assigned to the user."
        ], 201);
    }

/*
        // Check if the authenticated user is an admin
        $isAdmin = $user->role == 'Admin';

        // Set the otp_status based on the user's role
        $otpStatus = $isAdmin ? 1 : 2; // 1 for admin (accepted), 2 for user (pending)

        $OtpRequest = Otp_request::create(array_merge(
            $validator->validated(),
            [
                'otp_status' => $otpStatus // Set the otp_status based on the user's role
            ]
        ));
    */

    /**
     * Retrieve all otp_requests from Request
     * GET: /api/otp_request
     * @param Request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        // Include otp_code and used_at in the select statement
        $OtpRequests = Otp_request::select('id', 'room_id', 'user_id', 'Access_code', 'otp_status', 'generated_at','used_at', 'end_time', 'purpose')
            ->with(['room', 'user']) // Eager load related models if needed
            ->get();

        return response()->json([
            "ok" => true,
            "data" => $OtpRequests,
            "message" => "OTP requests have been retrieved successfully."
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
            'message' => 'OTP request details retrieved successfully.',
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
    public function update(Request $request, Otp_request $OtpRequest)
    {
        $validator = Validator::make($request->all(), [
            "room_id" => "sometimes|exists:rooms,id",
            "otp_status" => "sometimes|in:1,2,3",
            "Access_code" => "sometimes|string|regex:/^\d{6}$/",
            "generated_at" => "sometimes|date",
            'used_at' => "sometimes|date_format:H:i",
            "end_time" => "sometimes|date_format:H:i|after:used_at",
            "purpose" => "sometimes|string|min:4",
        ]);

        if ($validator->fails()) {
            return response()->json([
                "ok" => false,
                "message" => "Validation failed!",
                "errors" => $validator->errors()
            ], 400);
        }

        $OtpRequest->update($validator->validated());

        return response()->json([
            "ok" => true,
            "message" => "OTP request updated successfully.",
            "data" => $OtpRequest
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
            "message" => "Otp Request has been Deleted!."
        ], 201);
    }
}
