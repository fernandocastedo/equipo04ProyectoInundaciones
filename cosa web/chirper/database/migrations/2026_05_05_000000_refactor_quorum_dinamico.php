<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Migración: Refactorización a Quórum Dinámico
 *
 * Elimina las columnas calculadas de `inundaciones` (puntos_quorum,
 * intensidad_actual, expira_at) que ahora se computan al vuelo.
 * Agrega `peso` a `reportes` para poder sumar puntos por reporte.
 * Normaliza los valores de `estado` e `estado_validacion`.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ─────────────────────────────────────────────────────────────────
        // 1. Normalizar valores de `estado` en `inundaciones`
        //    Antes: 'in_progress' | 'resolved' | 'closed' | 'falso_reporte'
        //    Ahora: 'activa'      | 'terminada'            | 'falsa'
        // ─────────────────────────────────────────────────────────────────
        DB::statement("
            UPDATE inundaciones
            SET estado = CASE
                WHEN estado = 'in_progress'   THEN 'activa'
                WHEN estado IN ('resolved', 'closed') THEN 'terminada'
                WHEN estado = 'falso_reporte' THEN 'falsa'
                ELSE estado
            END
        ");

        // ─────────────────────────────────────────────────────────────────
        // 2. Eliminar columnas calculadas de `inundaciones`
        // ─────────────────────────────────────────────────────────────────
        Schema::table('inundaciones', function (Blueprint $table) {
            $table->dropColumn(['puntos_quorum', 'intensidad_actual', 'expira_at']);
        });

        // ─────────────────────────────────────────────────────────────────
        // 3. Agregar `peso` a `reportes`
        //    Calculado al insertar: +1 sin foto, +3 con foto.
        //    Se ubica después de `intensidad_propuesta`.
        // ─────────────────────────────────────────────────────────────────
        Schema::table('reportes', function (Blueprint $table) {
            $table->unsignedSmallInteger('peso')->default(1)->after('intensidad_propuesta');
        });

        // ─────────────────────────────────────────────────────────────────
        // 4. Normalizar `estado_validacion` en `reportes`
        //    Corrige el typo 'aprobada'/'rechazada' → 'aceptado'/'rechazado'
        // ─────────────────────────────────────────────────────────────────
        DB::statement("
            UPDATE reportes
            SET estado_validacion = CASE
                WHEN estado_validacion = 'aprobada' THEN 'aceptado'
                WHEN estado_validacion = 'rechazada' THEN 'rechazado'
                ELSE estado_validacion
            END
        ");

        // ─────────────────────────────────────────────────────────────────
        // 5. Recalcular `peso` de reportes existentes según foto_path
        // ─────────────────────────────────────────────────────────────────
        DB::statement("
            UPDATE reportes
            SET peso = CASE
                WHEN foto_path IS NOT NULL THEN 3
                ELSE 1
            END
        ");

        // ─────────────────────────────────────────────────────────────────
        // 6. Normalizar `estado` en `inundaciones` referenciado en
        //    refreshCitizenBanStatus (falso_reporte → falsa)
        // ─────────────────────────────────────────────────────────────────
        // Ya cubierto en el paso 1.
    }

    public function down(): void
    {
        // ─── Revertir peso en reportes ───
        Schema::table('reportes', function (Blueprint $table) {
            $table->dropColumn('peso');
        });

        // ─── Revertir columnas calculadas en inundaciones ───
        Schema::table('inundaciones', function (Blueprint $table) {
            $table->string('intensidad_actual')->default('media');
            $table->unsignedInteger('puntos_quorum')->default(0);
            $table->timestamp('expira_at')->nullable();
        });

        // ─── Revertir normalización de estados ───
        DB::statement("
            UPDATE inundaciones
            SET estado = CASE
                WHEN estado = 'terminada' THEN 'resolved'
                WHEN estado = 'falsa'     THEN 'falso_reporte'
                ELSE estado
            END
        ");

        DB::statement("
            UPDATE reportes
            SET estado_validacion = CASE
                WHEN estado_validacion = 'aceptado'  THEN 'aprobada'
                WHEN estado_validacion = 'rechazado' THEN 'rechazada'
                ELSE estado_validacion
            END
        ");
    }
};
