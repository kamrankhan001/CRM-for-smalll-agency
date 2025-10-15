<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('lead-import.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

Broadcast::channel('lead-export.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

Broadcast::channel('lead-assigned.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});
