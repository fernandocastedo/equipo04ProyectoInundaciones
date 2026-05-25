<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Mensaje de chat entre autoridades (1 a 1).
 *
 * Tabla PostgreSQL: chat_messages
 *
 * @property int    $id
 * @property string $sender_carnet
 * @property string $sender_name
 * @property string $receiver_carnet
 * @property string $message
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class ChatMessage extends Model
{
    protected $table = 'chat_messages';

    protected $fillable = [
        'sender_carnet',
        'sender_name',
        'receiver_carnet',
        'channel',
        'message',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Devuelve el nombre canónico del canal privado para dos carnets.
     * Siempre se ordena para que sea simétrico sin importar el orden.
     */
    public static function channelFor(string $a, string $b): string
    {
        $sorted = [$a, $b];
        sort($sorted);
        return 'chat.' . $sorted[0] . '.' . $sorted[1];
    }
}
