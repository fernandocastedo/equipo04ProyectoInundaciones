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
        if (! Schema::hasTable('chat_messages')) {
            Schema::create('chat_messages', function (Blueprint $table) {
                $table->id();
                $table->string('sender_carnet');
                $table->string('sender_name');
                $table->string('receiver_carnet');
                $table->text('message');
                $table->timestamps();

                $table->index(['sender_carnet', 'receiver_carnet']);
                $table->index('created_at');
            });

            return;
        }

        Schema::table('chat_messages', function (Blueprint $table) {
            if (! Schema::hasColumn('chat_messages', 'sender_carnet')) {
                $table->string('sender_carnet');
            }
            if (! Schema::hasColumn('chat_messages', 'sender_name')) {
                $table->string('sender_name');
            }
            if (! Schema::hasColumn('chat_messages', 'receiver_carnet')) {
                $table->string('receiver_carnet');
            }
            if (! Schema::hasColumn('chat_messages', 'message')) {
                $table->text('message');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};
