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
        Schema::create('otp_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('room_id');    // Foreign key to rooms table
            $table->unsignedBigInteger('user_id');    // Foreign key to users table
            $table->integer('Access_code')->unique()->nullable();     // The generated OTP code
            $table->enum('otp_status', ['1', '2', '3'])->default('2'); // OTP status
            $table->datetime('generated_at')->useCurrent(); // Timestamp when the OTP was generated
            $table->time('used_at')->nullable(); // Timestamp when the OTP was used
            $table->time("end_time"); //When the session ends
            $table->string("purpose"); //Purpose of the session


            // Foreign key definitions
            $table->foreign('room_id')->references('id')->on('rooms')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('otp_requests');
    }
};
