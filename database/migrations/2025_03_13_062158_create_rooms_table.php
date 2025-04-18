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
        Schema::create('rooms', function (Blueprint $table) {
            $table->bigIncrements('id')->unsigned();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('name');
            $table->integer('capacity');
            $table->enum('status', ['Available', 'Occupied', 'Pending'])->default('Available'); // Fixed typo here 1 = available | 2 = occupied | 3 = pending
            $table->string('location');
            $table->text('extension')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on("users")->onDelete("cascade");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
