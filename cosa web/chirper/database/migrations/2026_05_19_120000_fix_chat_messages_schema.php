<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('chat_messages')) {
            return;
        }

        Schema::table('chat_messages', function (Blueprint $table) {
            if (! Schema::hasColumn('chat_messages', 'sender_carnet')) {
                $table->string('sender_carnet')->nullable();
            }

            if (! Schema::hasColumn('chat_messages', 'sender_name')) {
                $table->string('sender_name')->nullable();
            }

            if (! Schema::hasColumn('chat_messages', 'receiver_carnet')) {
                $table->string('receiver_carnet')->nullable();
            }

            if (! Schema::hasColumn('chat_messages', 'message')) {
                $table->text('message')->nullable();
            }

            if (! Schema::hasColumn('chat_messages', 'created_at')) {
                $table->timestamp('created_at')->nullable();
            }

            if (! Schema::hasColumn('chat_messages', 'updated_at')) {
                $table->timestamp('updated_at')->nullable();
            }
        });

        DB::statement('CREATE INDEX IF NOT EXISTS chat_messages_sender_receiver_index ON chat_messages (sender_carnet, receiver_carnet)');
        DB::statement('CREATE INDEX IF NOT EXISTS chat_messages_created_at_index ON chat_messages (created_at)');
    }

    public function down(): void
    {
        if (! Schema::hasTable('chat_messages')) {
            return;
        }

        DB::statement('DROP INDEX IF EXISTS chat_messages_sender_receiver_index');
        DB::statement('DROP INDEX IF EXISTS chat_messages_created_at_index');
    }
};
