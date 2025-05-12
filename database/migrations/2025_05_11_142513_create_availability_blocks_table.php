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
        Schema::create('availability_blocks', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            // Link to either an agenda (stylist) or user
            $table->foreignId('agenda_id')->constrained();
            
            // Date and time range
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            
            // Type of block (regular hours, special hours, time off)
            $table->enum('block_type', ['regular', 'special', 'time_off'])->default('regular');
            
            // Optional recurring pattern
            $table->string('recurrence_pattern')->nullable()->comment('Format: weekly, monthly, etc.');
            
            // Optional end date for recurring blocks
            $table->date('recurrence_end_date')->nullable();
            
            // For overriding defaults (like holiday hours)
            $table->boolean('is_override')->default(false);
            
            // For special cases like seasonal hours
            $table->string('label')->nullable()->comment('e.g., "Christmas Hours", "Summer Schedule"');
            
            // Add index to improve query performance
            $table->index(['agenda_id', 'date', 'block_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('availability_blocks');
    }
};
