<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('chat.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});


Broadcast::channel('message.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
