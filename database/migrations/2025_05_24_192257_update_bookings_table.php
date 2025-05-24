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
        //make the remarks table nullable   
        Schema::table('bookings', function (Blueprint $table) {
            $table->longText('remarks')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //make the remarks table nullable
        Schema::table('bookings', function (Blueprint $table) {
            $table->longText('remarks')->nullable(false)->change();
        });
    }
};
