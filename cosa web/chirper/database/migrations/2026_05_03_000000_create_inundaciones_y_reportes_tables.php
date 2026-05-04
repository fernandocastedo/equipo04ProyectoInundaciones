<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Rename flood_reports to inundaciones
        Schema::rename('flood_reports', 'inundaciones');

        // 2. Modify inundaciones table
        Schema::table('inundaciones', function (Blueprint $table) {
            $table->renameColumn('latitude', 'latitud');
            $table->renameColumn('longitude', 'longitud');
            $table->renameColumn('severity', 'intensidad_actual');
            $table->renameColumn('status', 'estado');
            
            $table->integer('municipio_id')->nullable();
            $table->integer('puntos_quorum')->default(0);
            $table->timestamp('expira_at')->nullable();
        });

        // 3. Create reportes table
        Schema::create('reportes', function (Blueprint $table) {
            $table->id();
            $table->uuid('user_uuid')->nullable(); // Para reportes rápidos sin sesión
            $table->string('citizen_carnet', 20)->nullable(); // Para reportes normales con sesión
            
            $table->foreignId('inundacion_id')->nullable()->constrained('inundaciones')->cascadeOnDelete();
            
            $table->decimal('lat_gps', 10, 7)->nullable();
            $table->decimal('long_gps', 10, 7)->nullable();
            $table->decimal('lat_reporte', 10, 7);
            $table->decimal('long_reporte', 10, 7);
            
            $table->string('intensidad_propuesta')->default('media');
            $table->string('foto_path')->nullable();
            $table->string('estado_validacion')->default('pendiente');
            
            $table->jsonb('datos_clima_json')->nullable();
            
            $table->timestamps();

            $table->foreign('citizen_carnet')
                  ->references('carnet')
                  ->on('users')
                  ->cascadeOnDelete();
        });

        // 4. Create clima_cache table
        Schema::create('clima_cache', function (Blueprint $table) {
            $table->id();
            $table->integer('municipio_id')->nullable();
            $table->float('precipitacion_mm')->default(0);
            $table->timestamp('last_check')->useCurrent();
        });

        // Rename authority_responses relation if needed, though we might drop it or keep it.
        // We'll leave authority_responses for now, just update its foreign key.
        Schema::table('authority_responses', function (Blueprint $table) {
            $table->renameColumn('flood_report_id', 'inundacion_id');
        });
    }

    public function down(): void
    {
        Schema::table('authority_responses', function (Blueprint $table) {
            $table->renameColumn('inundacion_id', 'flood_report_id');
        });

        Schema::dropIfExists('clima_cache');
        Schema::dropIfExists('reportes');

        Schema::table('inundaciones', function (Blueprint $table) {
            $table->dropColumn(['municipio_id', 'puntos_quorum', 'expira_at']);
            $table->renameColumn('latitud', 'latitude');
            $table->renameColumn('longitud', 'longitude');
            $table->renameColumn('intensidad_actual', 'severity');
            $table->renameColumn('estado', 'status');
        });

        Schema::rename('inundaciones', 'flood_reports');
    }
};
