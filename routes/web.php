<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

Route::get('/update-room-status', function () {
    // Trigger the UpdateRoomStatuses command
    $exitCode = Artisan::call('edit:room-status');
    
    return response()->json([
        'message' => 'Room status update triggered.',
        'exit_code' => $exitCode
    ]);
});
