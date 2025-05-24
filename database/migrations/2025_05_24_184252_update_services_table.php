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
        //add column time
        Schema::table('services', function (Blueprint $table) {
            $table->integer('time')->required()->default(30);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //remove column time
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('time');
        });
    }
};
