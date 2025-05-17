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
        Schema::create('bookings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->dateTime('date');
            $table->string('name');
            $table->string('email');
            $table->string('telephone');
            $table->enum('gender', ['male', 'female']);
            $table->longText('remarks');
            $table->enum('status', ['pending', 'confirmed', 'cancelled','completed'])->default('pending');
            $table->foreignUuid('user_id')->nullable()->constrained('users')->default(null);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
