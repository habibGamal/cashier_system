<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Web orders channel - authorize all authenticated users
Broadcast::channel('web-orders', function ($user) {
    return $user !== null; // Any authenticated user can listen to web orders
});

// Shift specific channels
Broadcast::channel('shift.{shiftId}', function ($user, $shiftId) {
    return $user !== null; // Any authenticated user can listen to shift updates
});

// Kitchen channel
Broadcast::channel('kitchen', function ($user) {
    return $user !== null; // Any authenticated user can listen to kitchen updates
});
