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
        Schema::create('equipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->string('equipment_type')->comment('Tipo de equipo');
            $table->string('brand_model')->comment('Marca / Modelo');
            $table->string('serial_number')->comment('Número de serie');
            $table->string('internal_code')->nullable()->comment('Código interno');
            $table->boolean('is_bidirectional')->default(false)->comment('¿Es bidireccional?');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipments');
    }
};
