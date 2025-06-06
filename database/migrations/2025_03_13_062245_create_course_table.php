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
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            //$table->unsignedBigInteger("user_id");
            $table->string('name')->unique();
            $table->string('code')->unique();
            $table->integer('credit_unit');
            $table->string('description');
            $table->softDeletes();
            $table->timestamps();

            //$table->foreign('user_id')->references('id')->on("users")->onDelete("cascade");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
