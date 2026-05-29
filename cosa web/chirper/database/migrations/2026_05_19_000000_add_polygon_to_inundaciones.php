<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración: Polígono de Inundación Calculado por Elevación
 *
 * Agrega tres columnas a la tabla `inundaciones` para almacenar
 * el polígono de área de inundación calculado por el Job
 * CalcularPoligonoInundacion usando la API de Open Topo Data.
 *
 * - polygon_coords:            Array JSON de [lat, lng] que definen el polígono.
 * - polygon_calculado_at:      Timestamp de la última vez que el polígono fue calculado.
 * - polygon_editado_autoridad: Flag para indicar que la autoridad editó manualmente
 *                              el polígono, desactivando el recálculo automático.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inundaciones', function (Blueprint $table) {
            // Coordenadas del polígono como array JSON de pares [lat, lng]
            // Null hasta que el Job CalcularPoligonoInundacion lo calcule por primera vez.
            $table->jsonb('polygon_coords')->nullable()->after('longitud');

            // Timestamp del último cálculo del polígono (para saber si está actualizado)
            $table->timestamp('polygon_calculado_at')->nullable()->after('polygon_coords');

            // Si la autoridad editó el polígono manualmente, el Job no lo sobreescribe.
            $table->boolean('polygon_editado_autoridad')->default(false)->after('polygon_calculado_at');
        });
    }

    public function down(): void
    {
        Schema::table('inundaciones', function (Blueprint $table) {
            $table->dropColumn(['polygon_coords', 'polygon_calculado_at', 'polygon_editado_autoridad']);
        });
    }
};
