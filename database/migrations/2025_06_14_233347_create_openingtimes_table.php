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
        Schema::create('openingtimes', function (Blueprint $table) {
            $table->id();
            $table->string('day');
            $table->enum('status', ['open', 'gesloten'])->default('open');
            $table->time('open')->nullable();
            $table->time('close')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('openingtimes');
    }
};
