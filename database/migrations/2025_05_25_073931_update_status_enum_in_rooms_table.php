<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateStatusEnumInRoomsTable extends Migration
{
    public function up()
    {
        // For MySQL, you can use raw SQL to change the enum values
        DB::statement("ALTER TABLE rooms MODIFY status ENUM('Available','Pending') DEFAULT 'Available'");
    }

    public function down()
    {
        // In case you want to revert back
        DB::statement("ALTER TABLE rooms MODIFY status ENUM('Available','Pending','Occupied') DEFAULT 'Available'");
    }
}