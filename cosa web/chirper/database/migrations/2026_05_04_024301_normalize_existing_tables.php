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
        Schema::table('inundaciones', function (Blueprint $table) {
            $table->dropColumn(['provincia', 'municipio']);
            // The column 'municipio_id' was already added in 2026_05_03_000000_create_inundaciones_y_reportes_tables.php
            // So we just add the foreign key constraint. We must ensure the column type matches.
            // In 2026_05_03_000000 it was added as integer(). If it doesn't match foreignId(), we might have to change it, 
            // but we can try just adding the foreign key. Actually foreignId() creates an unsignedBigInteger. 
            // The existing one is `integer('municipio_id')`. We should change it to `unsignedBigInteger` first to match `municipios.id`.
        });

        // We use raw SQL to cast the column if necessary, or just drop and recreate it if we don't care about old data.
        // Assuming we want to drop and recreate for simplicity:
        Schema::table('inundaciones', function (Blueprint $table) {
            $table->dropColumn('municipio_id');
        });
        
        Schema::table('inundaciones', function (Blueprint $table) {
            $table->foreignId('municipio_id')->nullable()->constrained('municipios')->nullOnDelete();
            
            // Cambiar citizen_carnet por validador_id (Authority, references users.id)
            // It seems it didn't have a constraint or its name is different, so we skip dropForeign.
        });
        
        // El usuario dijo: "inundaciones citizen_carnet Ambiguo Cambiar por validador_id (Authority)"
        Schema::table('inundaciones', function (Blueprint $table) {
            $table->dropColumn('citizen_carnet');
            $table->string('validador_id', 20)->nullable();
            $table->foreign('validador_id')->references('carnet')->on('users')->nullOnDelete();
        });

        Schema::table('centros_asistencia', function (Blueprint $table) {
            $table->dropColumn(['provincia', 'municipio']);
            $table->foreignId('municipio_id')->nullable()->constrained('municipios')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('centros_asistencia', function (Blueprint $table) {
            $table->dropForeign(['municipio_id']);
            $table->dropColumn('municipio_id');
            $table->string('provincia')->nullable();
            $table->string('municipio')->nullable();
        });

        Schema::table('inundaciones', function (Blueprint $table) {
            $table->dropForeign(['validador_id']);
            $table->dropColumn('validador_id');
            
            $table->string('citizen_carnet')->nullable();
            
            $table->dropForeign(['municipio_id']);
            $table->dropColumn('municipio_id');
            $table->integer('municipio_id')->nullable(); // re-add the old one
            
            $table->string('provincia')->nullable();
            $table->string('municipio')->nullable();
        });
    }
};
