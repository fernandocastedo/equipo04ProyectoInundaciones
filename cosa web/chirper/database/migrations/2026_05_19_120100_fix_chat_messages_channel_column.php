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

        if (! Schema::hasColumn('chat_messages', 'channel')) {
            Schema::table('chat_messages', function (Blueprint $table) {
                $table->string('channel')->nullable();
            });
        }

        DB::statement("UPDATE chat_messages SET channel = CONCAT('chat.', LEAST(sender_carnet, receiver_carnet), '.', GREATEST(sender_carnet, receiver_carnet)) WHERE channel IS NULL AND sender_carnet IS NOT NULL AND receiver_carnet IS NOT NULL");
        DB::statement('ALTER TABLE chat_messages ALTER COLUMN channel DROP NOT NULL');
    }

    public function down(): void
    {
        // No-op: this is a compatibility migration.
    }
};
