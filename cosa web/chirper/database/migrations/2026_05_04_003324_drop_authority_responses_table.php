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
        Schema::dropIfExists('authority_responses');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('authority_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inundacion_id')->constrained('inundaciones')->onDelete('cascade');
            $table->string('authority_carnet');
            $table->text('response_text');
            $table->string('status_update')->nullable();
            $table->timestamps();
            
            $table->foreign('authority_carnet')->references('carnet')->on('users')->onDelete('cascade');
        });
    }
};
