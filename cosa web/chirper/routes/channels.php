<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Canal privado para chat 1-a-1 entre autoridades.
// Nombre canónico: chat.{carnet_menor}.{carnet_mayor}
Broadcast::channel('chat.{a}.{b}', function ($user, $a, $b) {
    // Solo autoridades no baneadas que sean parte del canal
    return $user->isAuthority()
        && ! $user->isBanned()
        && ($user->carnet === $a || $user->carnet === $b);
});
