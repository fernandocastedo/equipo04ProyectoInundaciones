<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración: Tabla de Víctimas
 *
 * Registra personas afectadas por una inundación.
 * Normalizada: enlazada a `inundaciones` (y transitivamente a municipio/provincia).
 * El campo `carnet` de la víctima es un dato de identificación libre (no FK a users),
 * ya que la víctima puede no estar registrada en el sistema.
 * `registrado_por` referencia al usuario autoridad que creó el registro.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('victimas', function (Blueprint $table) {
            $table->id();

            // ── Relación con la inundación (requerida) ──────────────────
            $table->foreignId('inundacion_id')
                  ->constrained('inundaciones')
                  ->cascadeOnDelete();

            // ── Datos de identificación de la víctima ───────────────────
            // carnet es texto libre: la víctima puede no estar en el sistema
            $table->string('carnet', 20)->nullable()->index();
            $table->string('nombre_completo', 255);
            $table->date('fecha_nacimiento')->nullable();

            // ── Estado de la víctima ─────────────────────────────────────
            // Valores permitidos: perdido | encontrado | herido | fallecido
            $table->string('estado', 20)->default('perdido');

            // ── Información adicional ────────────────────────────────────
            $table->string('foto_path', 255)->nullable();
            $table->text('descripcion')->nullable();

            // ── Auditoría: quién la registró (authority) ─────────────────
            $table->string('registrado_por', 20)->nullable();
            $table->foreign('registrado_por')
                  ->references('carnet')
                  ->on('users')
                  ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('victimas');
    }
};
