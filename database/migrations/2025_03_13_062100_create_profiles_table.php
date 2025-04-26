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
        Schema::create('profiles', function (Blueprint $table) {
            $table->unsignedBigInteger("user_id")->primary();
            $table->string("first_name",32);
            $table->string("middle_name",32)->nullable();
            $table->string("last_name",32);
            $table->date("birth_date")->nullable();
            $table->enum("gender", ["Male", "Female","Others"]);
            $table->string("contact_number"); //ito ay gagawing unique
            $table->enum("department", ["CIT", "COE", "ADMIN"]);
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();

            $table->foreign("user_id")->references("id")->on("users")->onDelete("cascade"); //use it to connect the users table
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
