<?php

namespace App\Http\Controllers;
use App\Models\Accesslog;
use App\Models\LoginHistory;
use Validator;
use Illuminate\Http\Request;

class AccessLogsController extends Controller
{

/*
    * Creates AccessLogs to inputs from Request
    *POST:: /api/AccessLogs
    * @param Request
    *@param AccessLog
    * @return \Illuminate\Http\Response
*/
    public function store (Request $request){
        $validator = Validator::make($request->all(),[
            'room_id' => 'required|exists:rooms,id',
            'user_id' => 'required|exists:users,id',
            'otp_request_id' => 'sometimes|exists:otp_requests,id',
            'accessed_at' => 'required|date',  // Timestamp for when the access occurred
            'used_at' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:used_at',
            'access_status' => 'required|in:1,2,3'
        ]);
        if ($validator->fails()) {
            return response()->json([
                "ok" => false,
                "message" => "Request didn't pass the validation!",
                "error" => $validator->errors()
            ], 400);
        }
    
        $validated = $validator->validated();
    
        $accessLog = AccessLog::create([
            'room_id' => $validated['room_id'],
            'user_id' => $validated['user_id'], 
            'otp_request_id' => $validated['otp_request_id'] ?? null,
            'accessed_at' => $validated['accessed_at'], 
            'used_at' => $validated['used_at'],
            'end_time' => $validated['end_time'],
            'access_status' => $validated['access_status']
        ]);

        LoginHistory::create([
            'user_id' => auth()->id(),
            'username' => auth()->user()->username,
            'role' => auth()->user()->role_id,
            'event' => 'accesslog',
            'action' => 'create',
        ]);
    
        return response()->json([
            "ok" => true,
            "data" => $accessLog,
            "message" => "Access log has been created"
        ], 201);
    }

/**
 * Retrive all AccessLogs from Request
 * GET: /api/accessLog/{accessLog}
 * @param Request
 * @param AccessLog
 * @return \Illuminate\Http\Response
 */
public function index()
{
    $accessLogs = AccessLog::with(['room', 'user.profile', 'otpRequest'])->get();

    return response()->json([
        "ok" => true,
        "data" => $accessLogs,
        "message" => "Access logs have been retrieved successfully.",
    ], 200);
}
/**
* Retrive specific AccessLog using id
* GET: /api/AccessLog/{AccessLog}
* @param Request
* @param  AccessLog
* @return \Illuminate\Http\Response
*/
public function show(Request $request, AccessLog $accessLog)
{
    $accessLog->load(['room', 'user', 'otp_request']);

    return response()->json([
        'ok' => true,
        'message' => 'Access log details have been retrieved successfully.',
        'data' => $accessLog,
    ], 200);
}
/**  Update specific AccessLogs using inputs from Request and id from URI
 * PATCH: /api/access_log/{access_log}
 * @param Request
 * @param AccessLog
 * @return \Illuminate\Http\Response
 */
    public function update (Request $request, AccessLog $accessLog){
        $validator = Validator::make($request->all(),[
            'room_id' => 'sometimes|exists:rooms,id',
            'user_id' => 'sometimes|exists:users,id',
            'otp_request_id' => 'sometimes|exists:otp_requests,id',
            'accessed_at' => 'sometimes|date',  // Timestamp for when the access occurred
            'used_at' => 'sometimes|date_format:H:i',
            'end_time' => 'sometimes|date_format:H:i|after:used_at',
            'access_status' => 'sometimes|in:1,2,3'
        ]);
        if ($validator->fails()) {
            return response()->json([
                "ok" => false,
                "message" => "Request didn't pass the validation!",
                "error" => $validator->errors()
            ], 400);
        }

        $validated = $validator->validated();
        // Update the existing schedule with validated data
        $accessLog->update($validated);

        LoginHistory::create([
            'user_id' => auth()->id(),
            'username' => auth()->user()->username,
            'role' => auth()->user()->role_id,
            'event' => 'accesslog',
            'action' => 'update',
        ]);

        return response()->json([
            "ok" => true,
            "data" => $accessLog,
            "message" => "Access_Log has been updated"
        ], 200);
    }

 /**
     * Soft delete a accesslog
     * @param $acceesslogId
     * @return \Illuminate\Http\JsonResponse
     */
    public function softDeleteAccesslog($accesslogId)
    {
        $accesslog = Accesslog::findOrFail($accesslogId);
        $accesslog->delete();

        return response()->json(['message' => 'Accesslog soft deleted successfully']);
    }

    /**
     * Restore a soft deleted user
     * @param $accesslogId
     * @return \Illuminate\Http\JsonResponse
     */
    public function restoreAccesslog($accesslogId)
    {
        $acceslog = Accesslog::withTrashed()->findOrFail($accesslogId);
        $acceslog->restore();

        return response()->json(['message' => 'Accesslog restored successfully']);
    }

/**
     * Force delete a user
     * @param $accesslogId
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($accesslogId)
    {
        $accesslogId = Accesslog::withTrashed()->findOrFail($accesslogId);
        $accesslogId->forceDelete();

        LoginHistory::create([
            'user_id' => auth()->id(),
            'username' => auth()->user()->username,
            'role' => auth()->user()->role_id,
            'event' => 'accesslog',
            'action' => 'delete',
        ]);
        return response()->json(['message' => 'Accesslog permanently deleted']);
    }

    /**
     * Retrive all accesslog soft deleted from Request
     * GET: /api/users/{user}
     * @param Request
     * @param Accesslog
     * @return \Illuminate\Http\JsonResponse
     */
    public function Archived(Request $request, Accesslog $accesslog)
    {
        $accesslog = Accesslog::onlyTrashed()->get();
        return response()->json([
            'message' => 'Soft-deleted accecsslogs retrieved successfully',
            'data' => $accesslog
        ], 200);
    }
}
