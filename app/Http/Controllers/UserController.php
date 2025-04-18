<?php

namespace App\Http\Controllers;

use App\Models\LoginHistory;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Validation\Rule;
use Validator;
class UserController extends Controller
{
/**
     * Create a User Account by registrations
     * @param Request
     * @return \Illuminate\Http\JsonResponse 
*/
     public function store (Request $request){
        $validator = Validator::make($request->all(), [
            "username" => [ "required","unique:users","min:4","regex:/^[a-zA-Z0-9._-]+$/",],
            "email" => "required|unique:users|email",
            "password" => "required|string|min:8|confirmed",
            "role_id" => "nullable|sometimes|in:Admin,user",
            "first_name" => "required|string|min:2",
            "middle_name" => "nullable|sometimes|string|min:2",
            "last_name" => "required|string|min:2",
            "birth_date" => "required|date|before:tomorrow",
            'gender' => ['required', Rule::in(['Male', 'Female', 'Others'])],
            "contact_number" => ["required", "string", "min:11", "regex:/^(09|\+639)\d{9}$/", "not_regex:/[a-zA-Z]/" ], // Matches valid Philippine contact numbers.
            'department' => ['required', Rule::in(['CIT', 'COE', 'OTHERS'])],
        ]);
        if ($validator->fails()){
            return $this->BadRequest($validator);
     }

     $validated = $validator->validated();

    $user = User::create($validator->safe()->only("username","email","password","role_id"));
        //User::create([
        //    "name" => $validated ["name"],
        //    "email"=> $validated ["email"],
        //    "password"=> $validated ["password"],
        //]);
    $user->profile()->create($validator->safe()->except("username","email","password","role_id"));
    $user->profile;
    
     // Log the user creation action
     LoginHistory::create([
        'user_id' => auth()->id(), // The currently authenticated user
        'username' => $user->username,
        'event' => 'user',
        'action' => 'create', // Action type
    ]);
    return $this->Created($user, "Account has been Created!");
}

    /**
     * Retrive all user from Request
     * GET: /api/users/{user}
     * @param Request
     * @param User
     * @return \Illuminate\Http\JsonResponse
     */
        public function index(){
            return response()->json([
                'ok' => true,
                'message' => 'Users has been Retrieved!.',
                'data' => User::with("profile")->get()
            ],201);
        }

    /**
     * Retrive specific user using id
     * GET: /api/users/{user}
     * @param Request
     * @param User
     * @return \Illuminate\Http\JsonResponse
     */
        public function show(Request $request, User $user){
            $user->profile;
            
            return response()->json([
                'ok'=> true,
                'message' => 'User Info has been Retrieved',
                'data' => $user
            ],200);
        }

        /**
     * Update specific user using inputs from Request and id from URI
     * PATCH: /api/users/{user}
     * @param Request
     * @param User
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            "username" => ["sometimes", "unique:users,username," . $user->id, "min:4", "regex:/^[a-zA-Z0-9._-]+$/"],
            "email" => "sometimes|email|unique:users,email," . $user->id,
            "password" => "sometimes|string|min:8|confirmed",
            "role_id" => "nullable|sometimes|in:Admin,user",
            "first_name" => "sometimes|string|min:2",
            "middle_name" => "nullable|sometimes|string|min:2",
            "last_name" => "sometimes|string|min:2",
            "birth_date" => "sometimes|date|before:tomorrow",
            'gender' => ['sometimes', Rule::in(['Male', 'Female', 'Others'])],
            "contact_number" => ["sometimes", "string", "min:11", "regex:/^(09|\+639)\d{9}$/"], 
            "department" => ["sometimes", Rule::in(["CIT", "COE", "OTHERS"])],
        ]);
    
        if ($validator->fails()) {
            return $this->BadRequest($validator);
        }
    
        $user_input = $validator->safe()->only(["username","email","role_id",]);
        $profile_input = $validator->safe()->except(["username","email","role_id"]);
    
        $user->update($user_input);
        $user->profile()->update($profile_input);
        $user->profile;
        

        $authenticatedUser = auth()->user();
        $role = $authenticatedUser->role_id;
        LoginHistory::create([
            'user_id' => auth()->id(), // The currently authenticated user
            'username' => $user->username,
            'role' => $role,
            'event' => 'user',
            'action' => 'update', // Action type
        ]);
        
        return response()->json([
            "ok" => true,
            "message" => "User Info has been Updated",
            "data" => $user
        ], 200);
    }

     /**
     * Soft delete a user
     * @param $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function softDeleteUser($userId)
    {
        $user = User::findOrFail($userId);
        $user->delete();

        return response()->json(
            [
                'ok' =>true,
                'data' =>$user,
                'message' => 'User soft deleted successfully']);
    }

    /**
     * Restore a soft deleted user
     * @param $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function restore($Id)
    {
        $data = User::withTrashed()->findOrFail($Id);
        $data->restore();

        return response()->json(['ok' => true, 'data' =>$Id, 'message' => 'User restored successfully']);
    }

    /**
     * Force delete a user
     * @param $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($userId)
    {
        $user = User::withTrashed()->findOrFail($userId);
        $user->forceDelete();

        return response()->json(['message' => 'User permanently deleted']);
    }

    /**
     * Display all soft-deleted users.
     * @return \Illuminate\Http\JsonResponse
     */
    
   /**
     * Retrive all user soft deleted from Request
     * GET: /api/users/{user}
     * @param Request
     * @param User
     * @return \Illuminate\Http\JsonResponse
     */
    public function Archived(Request $request, User $user)
    {
        $user = User::onlyTrashed()->get();

        return response()->json([
            'message' => 'Soft-deleted users retrieved successfully',
            'data' => $user
        ], 200);
    }

}

