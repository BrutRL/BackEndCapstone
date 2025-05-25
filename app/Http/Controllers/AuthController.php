<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\LoginHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;
use Validator;

class AuthController extends Controller
{
    /**
     * Creates a User and Profile Data
     * @param Request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "username" => ["required", "unique:users", "min:4", "max:64", "regex:/^[a-zA-Z][a-zA-Z0-9]*$/"], // Alphanumeric, starts with a letter
            "email" => "required|unique:users|email|max:64",
            "password" => "required|string|min:8|confirmed",
            "role_id" => "nullable|sometimes|in:Admin,user",
            "first_name" => ["required", "string", "min:2", "max:32", "regex:/^[A-Z][a-zA-Z]*$/"], // Starts with a capital letter
            "middle_name" => ["nullable", "sometimes", "string", "min:2", "max:32", "regex:/^[A-Z][a-zA-Z]*$/"], // Starts with a capital letter
            "last_name" => ["required", "string", "min:2", "max:32", "regex:/^[A-Z][a-zA-Z]*$/"], // Starts with a capital letter
            "birth_date" => "required|date|before:tomorrow",
            'gender' => ['required', Rule::in(['Male', 'Female', 'Others'])],
            "contact_number" => ["required", "string", "min:11", "regex:/^(09|\+639)\d{9}$/", "not_regex:/[a-zA-Z]/"], // Matches valid Philippine contact numbers
            'department' => ['required', Rule::in(['CIT', 'COE', 'OTHERS'])],
        ]);

        if ($validator->fails()) {
            return $this->BadRequest($validator);
        }

        $validated = $validator->validated();

        $user = User::create($validator->safe()->only("username", "email", "password"));
        $user->profile()->create($validator->safe()->except("username", "email", "password"));
        $user->profile;

        return $this->Created($user, "Account has been Created!");
    }

    /**
     * Attempt to authenticate username and password
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "username" => "required",
            "password" => "required"
        ]);

        if ($validator->fails()) {
            return $this->BadRequest($validator, "All Fields are required!");
        }

        $validated = $validator->validated(); // Allowing the user to use either username or email for login
        if (!Auth::attempt([
            filter_var($validated["username"], FILTER_VALIDATE_EMAIL) ? "email" : "username" => $validated["username"],
            "password" => $validated['password']
        ])) {
            return $this->Unauthorized("Invalid Credentials!");
        }

        $user = auth()->user();
        $user->profile;

        // Log the login event
        LoginHistory::create([
            'user_id' => $user->id,
            'role' => auth()->user()->role_id,
            'username' => $user->username,
            'event' => 'login',
        ]);

        $token = $user->createToken("api")->accessToken;

        return $this->Ok([
            "user" => $user,
            "token" => $token,
        ], "Logged in Success!");
    }

    /**
     * Check the validity of the token
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkToken(Request $request)
    {
        $user = $request->user();
        $user->profile;

        return $this->Ok($user, "Token is valid!");
    }
     // Other methods...

    /**
     * Retrieve login/logout history for the authenticated user
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getLoginHistory(Request $request)
{
    $user = $request->user();
    $user->profile;

    // If admin, show all logs; if user, show only their logs
    if ($user->role_id === 'Admin') {
        $history = LoginHistory::all();
    } else {
        $history = LoginHistory::where('user_id', $user->id)->get();
    }

    return $this->Ok($history, "Login/Logout history retrieved successfully");
}

    /**
     * Logout the authenticated user
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
{
    $user = $request->user(); // <-- ito ang gamitin

    if (!$user) {
        return $this->Unauthorized("Invalid Token!");
    }

    // Log the logout event
    LoginHistory::create([
        'user_id' => $user->id,
        'username' => $user->username,
        'role' => $user->role_id,
        'event' => 'logout',
    ]);

    // Revoke the user's tokens
    $user->tokens()->delete();

    return $this->Ok([], "Logout successful");
}

 /**
 * Send a password reset link using the username.
 * POST: /api/forgot-password
 * @param \Illuminate\Http\Request $request
 * @return \Illuminate\Http\JsonResponse
 */
public function forgotPassword(Request $request)
{
    $validator = Validator::make($request->all(), [
        "username" => "required|exists:users,username",
    ]);

    if ($validator->fails()) {
        return response()->json([
            "ok" => false,
            "message" => "Invalid username!",
            "errors" => $validator->errors(),
        ], 400);
    }

    $user = User::where('username', $request->username)->first();

    if (!$user) {
        return response()->json([
            "ok" => false,
            "message" => "User not found!",
        ], 404);
    }

    $token = Password::getRepository()->create($user);

    $user->notify(new \App\Notifications\ResetPasswordNotification($token, $user->username));

    return response()->json([
        "ok" => true,
        "message" => "Password reset link has been sent to your email!",
    ], 200);
}
/**
 * Reset the user's password using the username.
 * POST: /api/reset-password
 * @param \Illuminate\Http\Request $request
 * @return \Illuminate\Http\JsonResponse
 */


public function resetPassword(Request $request)
{
    $validator = Validator::make($request->all(), [
        'username' => 'required|exists:users,username',
        'token' => 'required',
        'password' => 'required|string|min:8|confirmed',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'ok' => false,
            'message' => 'Invalid input!',
            'errors' => $validator->errors(),
        ], 400);
    }

    $user = User::where('username', $request->username)->first();

    if (!$user) {
        return response()->json([
            'ok' => false,
            'message' => 'User not found!',
        ], 404);
    }

    // Retrieve the password reset record
    $record = \DB::table('password_resets')->where('email', $user->email)->first();

    if (!$record || !Hash::check($request->token, $record->token)) {
        return response()->json([
            'ok' => false,
            'message' => 'Invalid or expired token!',
        ], 400);
    }

    // Update the user's password
    $user->forceFill([
        'password' => Hash::make($request->password),
    ])->save();

    // Delete the password reset record
    \DB::table('password_resets')->where('email', $user->email)->delete();

    return response()->json([
        'ok' => true,
        'message' => 'Password has been reset successfully!',
    ], 200);
}
 
}
