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
        Schema::table('reportes', function (Blueprint $table) {
            $table->string('address')->nullable()->after('intensidad_propuesta');
            $table->text('description')->nullable()->after('address');
        });

        Schema::table('inundaciones', function (Blueprint $table) {
            $table->dropColumn(['address', 'description']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inundaciones', function (Blueprint $table) {
            $table->string('address')->nullable();
            $table->text('description')->nullable();
        });

        Schema::table('reportes', function (Blueprint $table) {
            $table->dropColumn(['address', 'description']);
        });
    }
};
