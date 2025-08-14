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
        Schema::create('calibration_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('laboratory_config_id')->constrained()->onDelete('cascade');
            // $table->foreignId('equipment_id')->constrained()->onDelete('cascade');
             $table->unsignedBigInteger('equipment_id');
            $table->foreign('equipment_id')
                        ->references('id')
                        ->on('equipments')->onDelete('cascade'); 
            
            // Campos editables (sección Encabezado)
            $table->string('certificate_number')->comment('Número de certificado');
            $table->date('issue_date')->comment('Fecha de emisión');
            
            // Datos específicos de la calibración
            $table->date('calibration_date')->comment('Fecha de calibración');
            $table->string('calibration_location')->comment('Lugar de calibración');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calibration_reports');
    }
};
