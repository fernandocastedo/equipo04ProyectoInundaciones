<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('donaciones', function (Blueprint $table) {
            $table->unsignedBigInteger('inundacion_id')->nullable()->after('id');
            $table->foreign('inundacion_id')
                  ->references('id')
                  ->on('inundaciones')
                  ->onDelete('set null');

            $table->unsignedBigInteger('victima_id')->nullable()->after('inundacion_id');
            $table->foreign('victima_id')
                  ->references('id')
                  ->on('victimas')
                  ->onDelete('set null');

            $table->string('photo_path')->nullable()->after('usage_details');
        });

        // Actualizar estados existentes
        DB::table('donaciones')
            ->whereIn('status', ['recibido', 'en_uso'])
            ->update(['status' => 'en_inventario']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('donaciones', function (Blueprint $table) {
            $table->dropForeign(['inundacion_id']);
            $table->dropForeign(['victima_id']);
            
            $table->dropColumn(['inundacion_id', 'victima_id', 'photo_path']);
        });

        // Revertir estados a recibido (si se deshace la migración)
        DB::table('donaciones')
            ->where('status', 'en_inventario')
            ->update(['status' => 'recibido']);
    }
};
