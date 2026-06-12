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
        Schema::create('vehiculos', function (Blueprint $table) {
            $table->id();
            $table->string('placa')->unique();
            $table->enum('tipo', ['ambulancia', 'camion_rescate', 'camioneta'])->default('ambulancia');
            $table->enum('estado', ['activo', 'inactivo', 'mantenimiento'])->default('inactivo');
            $table->integer('capacidad')->nullable();
            $table->decimal('latitud', 10, 8)->nullable();
            $table->decimal('longitud', 10, 8)->nullable();
            $table->timestamp('ultima_ubicacion_at')->nullable();
            $table->string('encargado_carnet')->nullable();
            $table->foreign('encargado_carnet')->references('carnet')->on('users')->nullOnDelete();
            $table->unsignedInteger('centro_asistencia_id')->nullable();
            $table->foreign('centro_asistencia_id')->references('id_centro')->on('centros_asistencia')->nullOnDelete();
            $table->boolean('en_ruta')->default(false)->comment('Indica si el vehículo está actualmente respondiendo a una emergencia y debe ser rastreado.');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehiculos');
    }
};
