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
        Schema::create('login_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
           // $table->enum('event', ['login', 'logout']);
           $table->enum("role", ['admin', 'user'])->nullable(); // Changed to string for flexibility this is for login
            $table->string('event'); // Changed to string for flexibility this is for login
            $table->enum('action', ['create', 'update', 'delete'])->nullable(); // Changed to string for flexibility this is for logout
            $table->string("username");
            $table->timestamp("created_at")->useCurrent();
                
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('login_histories');
    }
};