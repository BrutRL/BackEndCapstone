<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('accesslogs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('room_id');    // Foreign key to the rooms table
            $table->unsignedBigInteger('user_id'); // Foreign key to the teachers table
            $table->unsignedBigInteger('otp_request_id'); // Foreign key to OTP requests table (if OTP used)
            $table->timestamp('accessed_at');         // Timestamp for when the access occurred
            $table->tinyInteger('access_status');      // Status: 1 = Success, 2 = Failed, 3 = etc.
            $table->time('used_at')->nullable(); // Timestamp when the OTP was used
            $table->time("end_time"); //When the session ends
            $table->softDeletes(); // Soft delete the access log
            //$table->timestamps();


            // Foreign key definitions
            $table->foreign('room_id')->references('id')->on('rooms')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('otp_request_id')->references('id')->on('otp_requests')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accesslogs');
    }
};
