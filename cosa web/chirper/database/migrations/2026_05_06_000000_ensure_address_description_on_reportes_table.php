<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Asegura columnas esperadas por ReporteController / modelo Reporte.
     * Útil si una migración anterior quedó registrada pero no aplicó el cambio en la BD.
     */
    public function up(): void
    {
        Schema::table('reportes', function (Blueprint $table) {
            if (! Schema::hasColumn('reportes', 'address')) {
                $table->string('address')->nullable();
            }
            if (! Schema::hasColumn('reportes', 'description')) {
                $table->text('description')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('reportes', function (Blueprint $table) {
            $toDrop = array_values(array_filter([
                Schema::hasColumn('reportes', 'address') ? 'address' : null,
                Schema::hasColumn('reportes', 'description') ? 'description' : null,
            ]));
            if ($toDrop !== []) {
                $table->dropColumn($toDrop);
            }
        });
    }
};
