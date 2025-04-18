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
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("room_id");
            $table->unsignedBigInteger("user_id");
            $table->unsignedBigInteger("course_id");
            $table->date("assigned_date")->useCurrent(); //Date of the scheduled session
            $table->date("end_date")->useCurrent(); //Date of the scheduled session
            $table->string("days_in_week"); //Days of the week the session is scheduled
            $table->time("start_time"); //When the session starts
            $table->time("end_time"); //When the session ends
            $table->string("year"); //Year of the scheduled session
            $table->enum('status', ['1', '2', '3']); // 1 = Confirmed || 2 = Pending || 3 = Cancel default = 2
            $table->softDeletes();
            $table->timestamps();  //Date of the scheduled session


           // $table->primary("room_id");
           // $table->primary("user_id");
           // $table->primary("course_id");


            $table->foreign('room_id')->references('id')->on('rooms')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign("course_id")->references("id")->on("courses")->onDelete("cascade");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
