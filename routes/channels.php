<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function (User $user, $id) {
    return $user->id === $id;
});

Broadcast::channel('all', function (User $user) {
    return $user?->id !== null ? true : false;
});
