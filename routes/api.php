<?php

use App\Http\Controllers\AccessLogsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\Otp_requestController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;



Route::post("/register", [AuthController::class,"register"]);
Route::post("/login", [AuthController::class,"login"]);
//Route::middleware("auth:api")->post("/logout", [AuthController::class, "logout"]);
Route::middleware("auth:api")->post("/logout", [AuthController::class, "logout"]);
Route::middleware("auth:api")->get("/checkToken", [AuthController::class,"checkToken"]);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);
Route::middleware("auth:api")->get("/login-history", [AuthController::class, "getLoginHistory"]);

Route::prefix("users")->middleware("auth:api")->group(function () {
    Route::post("/", [UserController::class, "store"]);
    Route::get("/", [UserController::class, "index"]);
    Route::get("/archive", [UserController::class, "archived"]);
    Route::get("/{user}", [UserController::class, "show"]);
    Route::patch("/{user}", [UserController::class, "update"]);
    Route::delete("/{user}", [UserController::class, "destroy"]); //force delete

    Route::post("/{user}/soft-delete", [UserController::class, "softDeleteUser"]);
    Route::post('/restore/{userId}', [UserController::class, 'restore']);

});

Route::prefix("courses")->middleware("auth:api")->group(function () {
    Route::post("/", [CourseController::class, "store"]);
    Route::get("/", [CourseController::class, "index"]);
    Route::get("/archive", [CourseController::class, "archivedCourse"]);
    Route::get("/{course}", [CourseController::class, "show"]);
    Route::patch("/{course}", [CourseController::class, "update"]);
    Route::delete("/{course}", [CourseController::class, "destroy"]);
    Route::post("/{course}/soft-delete", [CourseController::class, "softDeleteCourse"]);
    Route::post('/restore/{courseId}', [courseController::class, 'restoreCourse']);
});

Route::prefix("rooms")->group(function () {
   Route::post("/", [RoomController::class, "store"])->middleware("auth:api");
   Route::get("/", [RoomController::class, "index"]);
   Route::get("/{room}", [RoomController::class, "show"]);
   Route::patch("/{room}", [RoomController::class, "update"])->middleware("auth:api");
   Route::delete("/{room}", [RoomController::class, "destroy"])->middleware("auth:api");
    Route::post('/{room}/archive', [RoomController::class, 'archiveRoom']);
});

Route::prefix("schedules")->middleware("auth:api")->group(function () {
    Route::post("/", [ScheduleController::class, "store"]);
    Route::get("/", [ScheduleController::class, "index"]);
    Route::get("/{schedule}", [ScheduleController::class, "show"]);
   // Route::get("/archive", [ScheduleController::class, "archivedSchedule"]);
    Route::patch("/{schedule}", [ScheduleController::class, "update"]);
    Route::delete("/{schedule}", [ScheduleController::class, "destroy"]);
    Route::post('/{schedule}/access', [ScheduleController::class, 'accessRoom']);

    Route::post("/{schedule}/soft-delete", [ScheduleController::class, "softDeleteSchedule"]);
    Route::post('/restore/{scheduleId}', [ScheduleController::class, 'restoreSchedule']);
    Route::get("/archive", [ScheduleController::class, "archivedSchedule"]);
});

Route::prefix("otp_requests")->group(function () {
    Route::post("/", [Otp_requestController::class, "store"]);
    Route::get("/", [Otp_requestController::class, "index"]);
    Route::get("/{otp_request}", [Otp_requestController::class, "show"]);
    Route::patch("/{otp_request}", [Otp_requestController::class, "update"]);
    Route::delete("/{otp_request}", [Otp_requestController::class, "destroy"]);
    Route::post('/{otp_request}/archive', [Otp_requestController::class, 'archiveOtpRequest']);


     // New route for OTP verification
     Route::post("/verify", [Otp_requestController::class, "verifyOtp"]);
});

Route::prefix("access_logs")->middleware("auth:api")->group(function () {
    Route::post("/", [AccessLogsController::class, "store"]);
    Route::get("/", [AccesslogsController::class, "index"]);
    Route::get("/{access_log}", [AccesslogsController::class, "show"]);
    Route::patch("/{access_log}", [AccesslogsController::class, "update"]);
    Route::delete("/{access_log}", [AccesslogsController::class, "destroy"]);
    Route::post('/{access_log}/archive', [AccesslogsController::class, 'archiveAccessLog']);
});

