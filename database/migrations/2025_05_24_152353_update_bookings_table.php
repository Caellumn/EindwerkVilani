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
        //add end time to bookings table in date time
        Schema::table('bookings', function (Blueprint $table) {
            $table->dateTime('end_time')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //remove end time from bookings table
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('end_time');
        });
    }
};
