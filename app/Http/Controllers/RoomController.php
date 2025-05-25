<?php

namespace App\Http\Controllers;

use App\Models\LoginHistory;
use App\Models\Room;
use Illuminate\Support\Facades\Storage;
use Validator;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    /**
     * Creates a Room based on inputs from Request
     * POST: /api/room
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
{
    // Validate the request
    $validator = Validator::make($request->all(), [
        "user_id" => "nullable|exists:users,id",
        "name" => ["required", "string", "min:2", "regex:/^[A-Z][a-zA-Z0-9]*$/", "unique:rooms,name"],
        "capacity" => "required|integer|min:1|max:50",
        "status" => "sometimes|integer|min:1",
        "location" => "required|string|min:8|max:255",
        'image' => "required|image|mimes:jpeg,jpg,png|max:32000" // Ensure the image is required and valid
    ], [
        "name.unique" => "The Room name already exists. Please choose a different name."
    ]);

    if ($validator->fails()) {
        return response()->json([
            'ok' => false,
            'message' => "Request didn't pass the validation!",
            'errors' => $validator->errors() // Return actual validation errors
        ], 400);
    }

    // Store the room data
    $validated = $validator->validated();
    if (isset($validated['image'])) {
        $image = $request->file("image");
    }
    $room = Room::create([
        'name' => $validated['name'],
        'capacity' => $validated['capacity'],
        'status' => $validated['status'] ?? 1,
        'location' => $validated['location'],
        'extension' => isset($image) ? $image->getClientOriginalExtension() : null
    ]);
    if (isset($validated['image'])) {
        $image->move(public_path('storage/uploads/rooms'), $room->id . '.' . $image->getClientOriginalExtension());
    }

    LoginHistory::create([
        'user_id' => auth()->id(),
        'username' => auth()->user()->username,
        'role' => auth()->user()->role_id,
        'event' => 'room',
        'action' => 'create',
    ]);

    return response()->json([
        'ok' => true,
        'data' => $room,
        'message' => "Room has been created"
    ], 201);
}

    /**
     * Retrieve all rooms
     * GET: /api/rooms
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        return response()->json([
            "ok" => true,
            "data" => Room::all(),
            "message" => "Rooms information has been retrieved."
        ], 200);
    }

    /**
     * Retrieve specific room using id
     * GET: /api/rooms/{room}
     * @param Room $room
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Room $room)
    {
        return response()->json([
            'ok' => true,
            'message' => 'Room specific information has been retrieved.',
            'data' => $room
        ], 200);
    }

    /**
     * Update specific room using inputs from Request and id from URI
     * PATCH: /api/rooms/{room}
     * @param Request $request
     * @param Room $room
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Room $room)
    {
        $validator = Validator::make($request->all(), [
            "name" => ["sometimes", "string", "min:2", "regex:/^[A-Z][a-zA-Z0-9]*$/", "unique:rooms,name," . $room->id], // Updated regex
            "capacity" => "sometimes|integer|min:1|max:50",
            "status" => "sometimes|in:1,3",
            "location" => "sometimes|string|min:8|max:255",
            'image' => "sometimes|image|mimes:jpeg,jpg,png|max:32000" // Ensure the image is valid
        ]);

        if ($validator->fails()) {
            return response()->json([
                'ok' => false,
                'message' => "Request didn't pass the validation!",
                'errors' => $validator->errors()
            ], 400);
        }

        $validated = $validator->validated();
        if(isset($validated['image'])){
          $image = $request->file("image");
          $validated['extension'] =  isset($image) ? $image->getClientOriginalExtension() : null;
          unset($validated['image']);
        }
        $room->update($validated);
        if(isset($image)){
          $image->move(public_path('storage/uploads/rooms'),  $room->id. '.' .  $image->getClientOriginalExtension());
          // Storage::put('/uploads/rooms/' . $room->id. '.' .  $image->getClientOriginalExtension(), $image);
        }

        LoginHistory::create([
            'user_id' => auth()->id(),
            'username' => auth()->user()->username,
            'role' => auth()->user()->role_id,
            'event' => 'room',
            'action' => 'update',
        ]);
        return response()->json([
            'ok' => true,
            'data' => $room,
            'messsage' => 'Room has been Updated.'
        ]);
    }


    /**
     * Delete specific room using id from URI
     * DELETE: /api/rooms/{room}
     * @param Room $room
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Room $room)
    {
        if ($room->image_url && Storage::disk('public')->exists($room->image_url)) {
            Storage::disk('public')->delete($room->image_url);
        }
        $room->delete();

        LoginHistory::create([
            'user_id' => auth()->id(),
            'username' => auth()->user()->username,
            'role' => auth()->user()->role_id,
            'event' => 'room',
            'action' => 'delete',
        ]);

        return response()->json([
            'ok' => true,
            'message' => "Room deleted successfully"
        ], 200);
    }

    
}