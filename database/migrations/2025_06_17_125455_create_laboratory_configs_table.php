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
        Schema::create('laboratory_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            // Datos configurables (sección Encabezado)

            
            $table->string('lab_name')->comment('Nombre del laboratorio');
            $table->string('onac_number')->comment('Número de acreditación ONAC');
            $table->string('document_title')->default('Certificado de Calibración')->comment('Título del documento');
            $table->timestamps();
            $table->softDeletes(); // Añade esta línea
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laboratory_configs');
    }
};
