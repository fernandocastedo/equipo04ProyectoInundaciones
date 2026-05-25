<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\ChatMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatMessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly ChatMessage $chatMessage) {}

    /**
     * Canal privado único para el par de usuarios.
     */
    public function broadcastOn(): Channel
    {
        return new PrivateChannel(
            ChatMessage::channelFor(
                $this->chatMessage->sender_carnet,
                $this->chatMessage->receiver_carnet,
            )
        );
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    public function broadcastWith(): array
    {
        return [
            'id'             => $this->chatMessage->id,
            'sender_carnet'  => $this->chatMessage->sender_carnet,
            'sender_name'    => $this->chatMessage->sender_name,
            'receiver_carnet'=> $this->chatMessage->receiver_carnet,
            'message'        => $this->chatMessage->message,
            'created_at'     => $this->chatMessage->created_at?->toISOString(),
        ];
    }
}
