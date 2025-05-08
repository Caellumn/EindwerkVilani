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
        Schema::table('serviceswithhairlengths', function (Blueprint $table) {
            $table->tinyInteger('status')->default(1)->comment('1 for active, 0 for inactive');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('serviceswithhairlengths', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};