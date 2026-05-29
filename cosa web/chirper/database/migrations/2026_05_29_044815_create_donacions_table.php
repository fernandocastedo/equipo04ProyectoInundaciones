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
        Schema::create('donaciones', function (Blueprint $table) {
            $table->id();
            
            // Relación con el centro de asistencia
            $table->unsignedBigInteger('centro_id');
            $table->foreign('centro_id')
                  ->references('id_centro')
                  ->on('centros_asistencia')
                  ->onDelete('restrict');
            
            // Relación con el donante (opcional, aunque sea anónima puede guardarse o puede estar vacío si es un invitado sin cuenta)
            // Asumimos que los donantes tienen carnet
            $table->string('donor_carnet')->nullable();
            $table->foreign('donor_carnet')
                  ->references('carnet')
                  ->on('users')
                  ->onDelete('set null');

            $table->text('items_description');
            $table->boolean('is_anonymous')->default(false);
            
            // Estado de la donación ('recibido', 'en_uso', 'entregado')
            $table->string('status')->default('recibido');
            
            // Detalles de uso: qué pasó con la donación
            $table->text('usage_details')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('donaciones');
    }
};
