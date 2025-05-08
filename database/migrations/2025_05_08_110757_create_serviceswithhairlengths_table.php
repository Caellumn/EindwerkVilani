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
        Schema::create('serviceswithhairlengths', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('service_id')->constrained(
                table: 'services',
                indexName: 'serviceswithhairlengths_service_id_foreign'
            );
            $table->foreignId('hairlength_id')->constrained(
                table: 'hairlengths',
                indexName: 'serviceswithhairlengths_hairlength_id_foreign'
            );
            $table->decimal('price', 8, 2);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('serviceswithhairlengths');
    }
};
