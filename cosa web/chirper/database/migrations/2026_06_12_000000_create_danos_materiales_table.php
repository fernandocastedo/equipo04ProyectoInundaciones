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
        Schema::create('danos_materiales', function (Blueprint $table) {
            $table->id();
            
            // Relación con la inundación a la que pertenece
            $table->foreignId('inundacion_id')
                  ->constrained('inundaciones')
                  ->cascadeOnDelete();
                  
            // Información del daño
            $table->string('tipo'); // puente, carretera, edificio, etc.
            $table->text('descripcion')->nullable();
            
            // Ubicación exacta
            $table->decimal('latitud', 10, 7);
            $table->decimal('longitud', 10, 7);
            
            // Estado actual
            $table->string('estado')->default('dañado'); // dañado, destruido, bloqueado
            
            // Auditoría: quién registró el daño (debe ser autoridad)
            $table->string('registrado_por', 20)->nullable();
            $table->foreign('registrado_por')
                  ->references('carnet')
                  ->on('users')
                  ->nullOnDelete();
                  
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('danos_materiales');
    }
};
